# Pelcom

Сайт производителя интерактивного оборудования и программного обеспечения. [Боевая версия](https://pelcom-tech.ru/) работает на «1С-Битрикс: Управление сайтом» на хостинге REG.RU. [GitHub Pages](https://dmitriy-gviskar.github.io/pelcom-site/) служит статическим предпросмотром из ветки `main`.

## Опубликованные разделы

| Раздел | Маршруты |
| --- | --- |
| Главная | [`/`](https://pelcom-tech.ru/) |
| Оборудование и отраслевые решения | [`/catalog/`](https://pelcom-tech.ru/catalog/), [`/solutions/`](https://pelcom-tech.ru/solutions/) |
| О компании, сервис, партнёры и контакты | [`/about/`](https://pelcom-tech.ru/about/), [`/service/`](https://pelcom-tech.ru/service/), [`/partners/`](https://pelcom-tech.ru/partners/), [`/contacts/`](https://pelcom-tech.ru/contacts/) |
| Программное обеспечение | [`/software/`](https://pelcom-tech.ru/software/), [Инфокиоск](https://pelcom-tech.ru/software/kiosk/), [Навигация](https://pelcom-tech.ru/software/nav/), [Очередь](https://pelcom-tech.ru/software/queue/) |
| Персональные данные | [Политика](https://pelcom-tech.ru/privacy/), [Согласие](https://pelcom-tech.ru/consent/) |

На первом экране используется рендер Pelcom из презентации заказчика размером 1661×935. В формах на главной и в контактах есть отдельное согласие. Заявки отправляются через серверный обработчик на `info@pelcom-tech.ru`. Подтверждение доставки во входящие отслеживается в списке задач.

## Работа с проектом

- Исходные HTML, CSS, JavaScript и изображения находятся в корне и `img/`. Генератор страниц Битрикса находится в `bitrix/`, файлы шаблона и серверных обработчиков — в `bitrix/overlay/`. Ядро CMS, база, ключи и пароли хранятся вне Git. [Локальная разработка и архитектура](bitrix/README.md)
- Изменения готовятся в предпросмотре и проверяются на затронутых страницах, включая разрешение изображений. Боевая публикация выполняется через SSH/SFTP. [Хостинг, DNS и порядок публикации](DEPLOY.md)
- Статус опубликованных изменений и оставшиеся задачи ведутся в [BACKLOG.md](BACKLOG.md).
