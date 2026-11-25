#!/usr/bin/env python3
"""Check the raster images that a Pelcom page actually displays."""
import argparse
from dataclasses import dataclass
from hashlib import sha256
from io import BytesIO
import json
import math
import mimetypes
import os
from pathlib import Path
from shutil import which
from urllib.parse import unquote, urljoin, urlsplit
from urllib.request import urlopen

try:
    from PIL import Image
    from playwright.sync_api import Error as BrowserError, sync_playwright
except ImportError as exc:
    raise SystemExit("Для проверки изображений установите Pillow и Playwright: python3 -m pip install Pillow playwright") from exc

HERE = Path(__file__).resolve().parent
SOURCE_ORIGIN = "http://pelcom-preview.local"
VIEWPORTS = ((1710, 885), (390, 844))
DENSITY = 2
# Allow one CSS pixel of rounding at the tested device density.
ROUNDING_PIXELS = DENSITY

MEASURE_IMAGES = """
async () => {
  await document.fonts.ready;
  const visible = Array.from(document.images).filter(img => {
    return img.getClientRects().length && getComputedStyle(img).visibility === 'visible';
  });
  for (const img of visible) img.loading = 'eager';
  await Promise.all(visible.map(img => img.decode().catch(() => {})));
  return visible.flatMap(img => {
    const rect = img.getBoundingClientRect();
    const loaded = img.naturalWidth > 0;
    if (loaded && (!rect.width || !rect.height)) return [];
    return [{
      url: img.currentSrc || img.src,
      loaded,
      width: rect.width,
      height: rect.height,
      fit: getComputedStyle(img).objectFit,
      footer: Boolean(img.closest('footer')),
    }];
  });
}
"""


@dataclass
class Raster:
    width: int
    height: int
    digest: str


def browser_path():
    mac_chrome = Path("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome")
    return (os.environ.get("PELCOM_CHROMIUM") or which("chromium")
            or which("google-chrome") or (str(mac_chrome) if mac_chrome.exists() else None))


def local_file(root, url, page_file):
    path = unquote(urlsplit(url).path)
    relative = page_file if path == "/" else path.lstrip("/")
    target = (root / relative).resolve()
    if not target.is_relative_to(root) or not target.is_file():
        return None
    if target.suffix.lower() not in {
        ".html", ".css", ".js", ".avif", ".bmp", ".gif", ".ico", ".jpeg", ".jpg",
        ".png", ".svg", ".webp", ".woff", ".woff2",
    }:
        return None
    return target


def serve_source(root, page_file, route):
    url = route.request.url
    if urlsplit(url).netloc != urlsplit(SOURCE_ORIGIN).netloc:
        route.abort()
        return
    target = local_file(root, url, page_file)
    if target is None:
        route.fulfill(status=404, body="")
        return
    content_type = mimetypes.guess_type(target.name)[0] or "application/octet-stream"
    if target.suffix in (".html", ".css", ".js"):
        content_type += "; charset=utf-8"
    route.fulfill(path=target, content_type=content_type)


def track_image_response(responses, response):
    request = response.request
    if request.resource_type != "image" or not response.ok:
        return
    while request:
        responses[request.url] = response
        request = request.redirected_from


def read_raster(responses, url):
    if url.startswith("data:"):
        with urlopen(url) as inline:
            mime = inline.headers.get_content_type()
            data = inline.read()
    else:
        response = responses.get(url)
        if not response:
            raise ValueError("нет успешного ответа на запрос изображения")
        mime = response.headers.get("content-type", "").split(";", 1)[0].lower()
        data = response.body()
    if mime == "image/svg+xml":
        return None
    with Image.open(BytesIO(data)) as image:
        return Raster(image.width, image.height, sha256(data).hexdigest())


def pixels_required(layout, raster):
    scale_x = layout["width"] / raster.width
    scale_y = layout["height"] / raster.height
    fit = layout["fit"]
    if fit in ("contain", "cover", "scale-down"):
        scale = max(scale_x, scale_y) if fit == "cover" else min(scale_x, scale_y)
        if fit == "scale-down":
            scale = min(1, scale)
        scale_x = scale_y = scale
    elif fit == "none":
        scale_x = scale_y = 1
    return (math.ceil(raster.width * scale_x * DENSITY),
            math.ceil(raster.height * scale_y * DENSITY))


def check_page(browser, url, route_path, exceptions, source=None, page_file=None):
    errors = []
    for width, height in VIEWPORTS:
        context = browser.new_context(
            viewport={"width": width, "height": height}, device_scale_factor=DENSITY,
            reduced_motion="reduce", service_workers="block",
        )
        try:
            context.add_init_script("localStorage.setItem('pelcom_analytics_v1', 'declined');")
            if source is not None:
                context.route("**/*", lambda route: serve_source(source, page_file, route))
            else:
                origin = urlsplit(url).netloc
                context.route("**/*", lambda route: route.continue_()
                              if urlsplit(route.request.url).netloc == origin else route.abort())
            page = context.new_page()
            responses = {}
            page.on("response", lambda response: track_image_response(responses, response))
            response = page.goto(url, wait_until="load", timeout=20000)
            if not response or not response.ok:
                raise ValueError(f"страница не загрузилась: {url}")
            layouts = page.evaluate(MEASURE_IMAGES)
            if route_path == "/" and page_file in (None, "pelcom-landing.html") and not layouts:
                errors.append(f"/ · {width}px · на главной не найдено изображений")
            checked = legacy = 0
            for layout in layouts:
                image_url = layout["url"]
                path = urlsplit(image_url).path
                label = path if not image_url.startswith("data:") else "встроенное изображение"
                try:
                    if not layout["loaded"]:
                        raise ValueError("изображение не загрузилось")
                    raster = read_raster(responses, image_url)
                    if raster is None:
                        continue
                except (OSError, ValueError, BrowserError) as exc:
                    errors.append(f"{route_path} · {width}px · {label} · {exc}")
                    continue

                checked += 1
                required_w, required_h = pixels_required(layout, raster)
                if required_w <= raster.width + ROUNDING_PIXELS and required_h <= raster.height + ROUNDING_PIXELS:
                    continue
                old = exceptions.get(route_path, {}).get(path)
                if old is None and layout["footer"]:
                    old = exceptions["shared_footer"].get(path)
                limit = old["max_pixels"].get(str(width)) if old else None
                if (limit and raster.digest == old["sha256"]
                        and required_w <= limit[0] + ROUNDING_PIXELS
                        and required_h <= limit[1] + ROUNDING_PIXELS):
                    legacy += 1
                    continue
                errors.append(
                    f"{route_path} · {width}px @ {DENSITY}× · {label} · "
                    f"файл {raster.width}×{raster.height}, нужно {required_w}×{required_h}"
                )
            print(f"{route_path} · {width}px @ {DENSITY}× · растровых изображений {checked}, прежних исключений {legacy}", flush=True)
        finally:
            context.close()
    return errors


def check_images(*, source=None, page_file="pelcom-landing.html", base_url=None, paths=("/",)):
    exceptions = json.loads((HERE / "image_resolution_exceptions.json").read_text())
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(executable_path=browser_path())
        try:
            errors = []
            for path in paths:
                url = urljoin(base_url or SOURCE_ORIGIN, path)
                errors.extend(check_page(browser, url, path, exceptions, source, page_file))
        finally:
            browser.close()
    for error in errors:
        print(error, flush=True)
    return not errors


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--source", type=Path, help="корень исходников")
    mode.add_argument("--url", help="адрес сайта для проверки сборки")
    parser.add_argument("--page", default="pelcom-landing.html", help="HTML-файл внутри --source")
    parser.add_argument("--path", action="append", help="проверяемый маршрут для --url, можно повторять")
    args = parser.parse_args()
    if args.path and (args.source or any(not path.startswith("/") or path.startswith("//") for path in args.path)):
        parser.error("--path принимает маршруты вида /catalog/ и используется с --url")
    try:
        passed = check_images(
            source=args.source.resolve() if args.source else None, page_file=args.page,
            base_url=args.url, paths=args.path or ("/",),
        )
    except (OSError, BrowserError, ValueError) as exc:
        parser.exit(1, f"Проверка изображений не выполнена. {exc}\n")
    if not passed:
        parser.exit(1, "Публикация остановлена. Исправьте указанные изображения.\n")


if __name__ == "__main__":
    main()
