<?php
// Это открывающий тег PHP — начало серверного кода.
declare(strict_types=1);
// Включаем строгую типизацию — PHP будет строго проверять типы данных (например, строка, число) в функциях, чтобы избежать ошибок.
?>
<!DOCTYPE html>
<!-- Объявляем тип документа как HTML5. Это говорит браузеру, что мы используем современный стандарт HTML. -->
<html lang="ru">
<!-- Открываем тег <html> — корневой элемент страницы. Атрибут lang="ru" указывает, что язык документа — русский. -->
<head>
    <!-- Открываем тег <head> — здесь содержатся метаданные, стили и подключения внешних файлов. -->
    <meta charset="UTF-8">
    <!-- Устанавливаем кодировку UTF-8, чтобы поддерживать кириллицу и другие символы. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Указываем, что страница адаптируется к ширине устройства (важно для мобильных). initial-scale=1.0 — начальный масштаб 100%. -->
    <title>БРИГАДА</title>
    <!-- Задаём заголовок страницы, который отображается во вкладке браузера: "БРИГАДА". -->
    
    <!-- Подключаем CSS-фреймворк Bootstrap 5 с CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Подключаем Bootstrap 5 — библиотеку стилей для упрощения дизайна (сетка, кнопки, формы). -->
    <!-- href: ссылка на файл с CDN (сеть доставки контента). -->
    <!-- integrity: проверка целостности файла для безопасности. -->
    <!-- crossorigin="anonymous": позволяет загружать файл с другого домена без передачи данных пользователя. -->

    <!-- Подключаем иконки Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Подключаем библиотеку иконок Bootstrap Icons для использования значков (например, домик, человек). -->
    <!-- href: ссылка на CSS-файл с иконками. -->

    <!-- Подключаем Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Подключаем Animate.css — библиотеку для анимаций (например, плавное появление, пульсация). -->
    <!-- href: ссылка на минимизированный CSS-файл с CDN. -->

    <!-- Подключаем шрифт Orbitron -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <!-- Подключаем шрифт Orbitron (жирный, вес 700) от Google Fonts для футуристического стиля заголовков. -->
    <!-- href: ссылка на файл шрифта. -->

    <style>
        /* Открываем тег <style> — здесь задаём собственные CSS-стили для страницы. */
        :root {
            /* Определяем CSS-переменные (custom properties) в псевдоклассе :root для глобального использования. */
            --main-bg-color: #1a0525;
            /* Основной цвет фона: тёмно-фиолетовый (#1a0525). */
            --secondary-bg-color: #0d0413;
            /* Вторичный цвет фона: ещё более тёмный фиолетовый (#0d0413). */
            --text-color: #e0e0e0;
            /* Цвет текста: светло-серый (#e0e0e0). */
            --purple-accent: #c77dff;
            /* Акцентный фиолетовый цвет (#c77dff). */
            --purple-dark: #6f42c1;
            /* Тёмно-фиолетовый оттенок (#6f42c1). */
            --purple-light: #9c4dff;
            /* Светло-фиолетовый оттенок (#9c4dff). */
        }
        
        html, body {
            /* Стили для элементов <html> и <body>. */
            height: 100%;
            /* Высота 100% от окна браузера, чтобы страница занимала весь экран. */
            margin: 0;
            /* Убираем внешние отступы по умолчанию. */
            padding: 0;
            /* Убираем внутренние отступы по умолчанию. */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Задаём шрифт: сначала Segoe UI, затем запасные варианты (Tahoma и т.д.). */
        }
        
        body {
            /* Дополнительные стили только для <body>. */
            display: flex;
            /* Используем flexbox для управления расположением содержимого. */
            flex-direction: column;
            /* Элементы внутри <body> (шапка, контент, подвал) располагаются вертикально. */
            min-height: 100vh;
            /* Минимальная высота 100% от высоты окна (vh = viewport height), чтобы страница всегда была полной. */
            background: radial-gradient(ellipse at bottom, var(--main-bg-color) 0%, var(--secondary-bg-color) 100%);
            /* Радиальный градиент фона: эллипс от низа (#1a0525) к верху (#0d0413). */
            color: var(--text-color);
            /* Цвет текста по умолчанию: светло-серый (#e0e0e0). */
            overflow-x: hidden;
            /* Убираем горизонтальную прокрутку, если контент выходит за пределы. */
            position: relative;
            /* Позиция relative для правильного наложения декоративных слоёв (звёзды, метеориты). */
        }

        .text-purple { color: var(--purple-accent) !important; }
        /* Класс для текста фиолетового цвета (#c77dff). !important переопределяет другие стили. */
        .bg-purple { background-color: var(--purple-accent) !important; }
        /* Класс для фона фиолетового цвета (#c77dff). */
        .bg-purple-dark { background-color: var(--purple-dark) !important; }
        /* Класс для тёмно-фиолетового фона (#6f42c1). */
        .border-purple { border-color: var(--purple-accent) !important; }
        /* Класс для фиолетовой границы (#c77dff). */

        .cosmic-header {
            /* Стили для шапки страницы (<header>). */
            background: var(--purple-dark);
            /* Фон: тёмно-фиолетовый (#6f42c1). */
            padding: 0.5rem 0;
            /* Внутренние отступы: 0.5rem (8px) сверху и снизу, 0 по бокам. */
            color: var(--text-color);
            /* Цвет текста: светло-серый (#e0e0e0). */
            z-index: 1000;
            /* Высокий уровень слоя, чтобы шапка была поверх других элементов. */
            border-bottom: 1px solid var(--purple-accent);
            /* Нижняя граница: 1px, сплошная, фиолетовая (#c77dff). */
        }

        .header-content {
            /* Стили для контейнера внутри шапки. */
            display: flex;
            /* Flexbox для горизонтального расположения элементов. */
            justify-content: space-between;
            /* Элементы распределяются по краям контейнера. */
            align-items: center;
            /* Элементы выравниваются по центру по вертикали. */
        }

        .header-left {
            /* Стили для левой части шапки (логотип и текст). */
            display: flex;
            /* Flexbox для горизонтального выравнивания содержимого. */
            align-items: center;
            /* Выравнивание по центру по вертикали. */
        }

        .header-title {
            /* Стили для заголовка "БРИГАДА". */
            font-family: 'Orbitron', sans-serif;
            /* Шрифт Orbitron для футуристического вида. */
            font-size: 3rem;
            /* Размер шрифта: 3rem (48px). */
            font-weight: 700;
            /* Жирность шрифта: 700 (bold). */
            color: var(--purple-accent);
            /* Цвет: фиолетовый (#c77dff). */
            text-shadow: 0 0 5px var(--purple-accent);
            /* Тень текста: размытие 5px, цвет фиолетовый (#c77dff), для эффекта свечения. */
        }

        .header-subtitle {
            /* Стили для подзаголовка в шапке. */
            font-size: 1.2rem;
            /* Размер шрифта: 1.2rem (19px). */
            color: var(--text-color);
            /* Цвет: светло-серый (#e0e0e0). */
            margin-left: 5rem;
            /* Отступ слева: 5rem (80px), чтобы отодвинуть от заголовка. */
        }

        .header-right {
            /* Стили для правой части шапки (кнопки). */
            display: flex;
            /* Flexbox для горизонтального расположения кнопок. */
            align-items: center;
            /* Выравнивание кнопок по центру по вертикали. */
        }

        .cosmic-btn {
            /* Общие стили для кнопок. */
            display: inline-flex;
            /* Inline-flex: кнопка ведёт себя как строчный элемент, но внутри — flexbox. */
            align-items: center;
            /* Выравнивание содержимого кнопки (текст, иконка) по центру по вертикали. */
            padding: 0.5rem 1rem;
            /* Внутренние отступы: 0.5rem (8px) сверху и снизу, 1rem (16px) по бокам. */
            border-radius: 6px;
            /* Скругление углов: 6px. */
            font-weight: 500;
            /* Жирность шрифта: 500 (средняя). */
            font-size: 1rem;
            /* Размер шрифта: 1rem (16px). */
            transition: all 0.3s ease;
            /* Плавный переход для всех изменений (цвет, размер) за 0.3 секунды. */
            border: none;
            /* Без границы по умолчанию. */
            text-decoration: none;
            /* Убираем подчёркивание текста (для ссылок). */
            margin-left: 0.5rem;
            /* Отступ слева: 0.5rem (8px), чтобы кнопки не слипались. */
        }

        .cosmic-btn-outline {
            /* Стили для кнопок с контуром (например, "Главная"). */
            background-color: transparent;
            /* Прозрачный фон. */
            border: 1px solid var(--purple-accent);
            /* Граница: 1px, сплошная, фиолетовая (#c77dff). */
            color: var(--purple-accent);
            /* Цвет текста: фиолетовый (#c77dff). */
        }

        .cosmic-btn-outline:hover {
            /* Стили для кнопок с контуром при наведении. */
            background-color: rgba(106, 13, 173, 0.1);
            /* Полупрозрачный фиолетовый фон (RGB с прозрачностью 10%). */
            color: #d0c0f0;
            /* Цвет текста: светло-фиолетовый (#d0c0f0). */
            transform: translateY(-2px);
            /* Сдвиг вверх на 2px для эффекта поднятия. */
        }

        .cosmic-btn-primary {
            /* Стили для основных кнопок (например, "Добавить"). */
            background-color: var(--purple-accent);
            /* Фон: фиолетовый (#c77dff). */
            color: white;
            /* Цвет текста: белый. */
        }

        .cosmic-btn-primary:hover {
            /* Стили для основных кнопок при наведении. */
            background-color: #7b1de8;
            /* Более тёмный фиолетовый фон (#7b1de8). */
            transform: translateY(-2px);
            /* Сдвиг вверх на 2px. */
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
            /* Тень: смещение вниз 4px, размытие 12px, полупрозрачный фиолетовый. */
        }

        main {
            /* Стили для основного содержимого страницы (<main>). */
            flex-grow: 1;
            /* Растягивается, чтобы занять всё доступное пространство между шапкой и подвалом. */
            display: flex;
            /* Flexbox для внутреннего содержимого. */
            flex-direction: column;
            /* Элементы внутри <main> располагаются вертикально. */
        }

        .stars {
            /* Стили для контейнера звёзд. */
            position: fixed;
            /* Фиксированное положение — не прокручивается с контентом. */
            top: 0;
            /* От верха страницы. */
            left: 0;
            /* От левого края. */
            width: 100%;
            /* Ширина 100%. */
            height: 100%;
            /* Высота 100%. */
            z-index: 0;
            /* Самый нижний слой, под контентом. */
            pointer-events: none;
            /* Пропускает клики сквозь себя (для интерактивности страницы). */
        }
        
        .star {
            /* Стили для отдельных звёзд. */
            position: absolute;
            /* Абсолютное положение внутри контейнера .stars. */
            border-radius: 50%;
            /* Круглая форма. */
            animation: twinkle 5s infinite ease-in-out;
            /* Анимация "twinkle": длительность 5 секунд, бесконечная, с плавным ускорением/замедлением. */
            opacity: 0;
            /* Начальная прозрачность: 0 (невидима). */
        }
        
        @keyframes twinkle {
            /* Определяем анимацию "twinkle" для звёзд. */
            0%, 100% { opacity: 0; }
            /* В начале (0%) и конце (100%) звезда невидима. */
            50% { opacity: 1; }
            /* На середине (50%) звезда полностью видима. */
        }

        .meteor-shower {
            /* Стили для контейнера метеоритов. */
            position: fixed;
            /* Фиксированное положение. */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            /* Слой выше звёзд, но ниже контента. */
            pointer-events: none;
            /* Пропускает клики. */
        }

        .meteor {
            /* Стили для отдельных метеоритов. */
            position: absolute;
            /* Абсолютное положение внутри .meteor-shower. */
            width: 2px;
            /* Ширина: 2px (тонкая линия). */
            height: 50px;
            /* Высота: 50px (длина следа). */
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.8), rgba(199, 125, 255, 0));
            /* Градиент фона: от белого (80% непрозрачности) к прозрачному фиолетовому. */
            box-shadow: 0 0 10px rgba(199, 125, 255, 0.5);
            /* Тень: размытие 10px, полупрозрачный фиолетовый, для эффекта свечения. */
            animation: meteor-fall linear infinite;
            /* Анимация "meteor-fall": линейная, бесконечная. */
            transform-origin: top left;
            /* Точка трансформации: верхний левый угол. */
            transform: rotate(-45deg);
            /* Поворот на -45 градусов (падающий метеорит). */
        }

        @keyframes meteor-fall {
            /* Определяем анимацию "meteor-fall" для метеоритов. */
            0% {
                opacity: 1;
                /* Начальная прозрачность: 100%. */
                transform: translate(0, 0) rotate(-45deg);
                /* Начальная позиция: без смещения, с поворотом -45 градусов. */
            }
            100% {
                opacity: 0;
                /* Конечная прозрачность: 0 (исчезает). */
                transform: translate(500px, 500px) rotate(-45deg);
                /* Смещение на 500px вправо и вниз, сохраняя поворот. */
            }
        }

        .gradient-overlay {
            /* Стили для декоративного слоя с градиентом. */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 30%, rgba(92, 0, 153, 0.15) 0%, transparent 25%),
                        radial-gradient(circle at 80% 70%, rgba(156, 39, 176, 0.15) 0%, transparent 25%);
            /* Два радиальных градиента: один в левом верхнем углу, другой в правом нижнем, оба полупрозрачные, для эффекта глубины. */
            z-index: 0;
            /* Нижний слой. */
            pointer-events: none;
            /* Пропускает клики. */
        }

        footer {
            /* Стили для подвала (<footer>). */
            flex-shrink: 0;
            /* Не сжимается, сохраняет размер (часть flexbox). */
            background: linear-gradient(to top, var(--secondary-bg-color), var(--main-bg-color));
            /* Градиент фона: от тёмного (#0d0413) снизу к чуть светлее (#1a0525) сверху. */
            border-top: 1px solid var(--purple-accent);
            /* Верхняя граница: 1px, фиолетовая (#c77dff). */
            padding: 1rem 0;
            /* Внутренние отступы: 1rem (16px) сверху и снизу. */
            color: var(--text-color);
            /* Цвет текста: светло-серый (#e0e0e0). */
        }

        footer h5 {
            /* Стили для заголовков <h5> в подвале. */
            font-family: 'Orbitron', sans-serif;
            /* Шрифт Orbitron. */
            color: var(--purple-accent);
            /* Цвет: фиолетовый (#c77dff). */
            text-shadow: 0 0 5px var(--purple-accent);
            /* Тень: размытие 5px, фиолетовая. */
        }

        footer p {
            /* Стили для параграфов <p> в подвале. */
            color: var(--text-color);
            /* Цвет: светло-серый (#e0e0e0). */
            font-size: 0.9rem;
            /* Размер шрифта: 0.9rem (14px). */
        }

        @media (max-width: 768px) {
            /* Адаптивные стили для экранов до 768px (планшеты). */
            .header-content {
                flex-direction: column;
                /* Переключаем на вертикальное расположение. */
                text-align: center;
                /* Центрируем текст. */
            }

            .header-left {
                margin-bottom: 0.5rem;
                /* Отступ снизу: 0.5rem (8px). */
            }

            .header-title {
                font-size: 1.2rem;
                /* Уменьшаем заголовок до 1.2rem (19px). */
            }

            .header-subtitle {
                font-size: 0.8rem;
                /* Уменьшаем подзаголовок до 0.8rem (13px). */
                margin-left: 0;
                /* Убираем отступ слева. */
            }

            .header-right {
                flex-direction: column;
                /* Кнопки располагаются вертикально. */
                margin-top: 0.5rem;
                /* Отступ сверху: 0.5rem (8px). */
            }

            .cosmic-btn {
                margin-left: 0;
                /* Убираем отступ слева. */
                margin-top: 0.5rem;
                /* Добавляем отступ сверху: 0.5rem (8px). */
                padding: 0.4rem 0.8rem;
                /* Уменьшаем отступы: 0.4rem (6px) сверху/снизу, 0.8rem (13px) по бокам. */
                font-size: 0.9rem;
                /* Уменьшаем шрифт до 0.9rem (14px). */
            }
        }

        @media (max-width: 576px) {
            /* Адаптивные стили для экранов до 576px (телефоны). */
            .header-title {
                font-size: 1.1rem;
                /* Ещё меньше заголовок: 1.1rem (18px). */
            }

            .header-subtitle {
                font-size: 0.75rem;
                /* Подзаголовок: 0.75rem (12px). */
            }

            .cosmic-btn {
                font-size: 0.85rem;
                /* Шрифт кнопок: 0.85rem (14px). */
                padding: 0.3rem 0.6rem;
                /* Ещё меньше отступы: 0.3rem (5px) сверху/снизу, 0.6rem (10px) по бокам. */
            }
        }

        /* Стили из index.php (оставляем только те, которые не связаны с модальным окном) */
        .cosmic-card {
            /* Стили для карточек (например, профиль человека). */
            background-color: rgba(35, 35, 45, 0.9);
            /* Полупрозрачный тёмный фон. */
            border: 1px solid #4a3a6a;
            /* Граница: 1px, фиолетовая (#4a3a6a). */
            border-radius: 10px;
            /* Скругление углов: 10px. */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            /* Тень: смещение вниз 4px, размытие 20px, чёрная с прозрачностью 30%. */
            backdrop-filter: blur(5px);
            /* Размытие фона за карточкой на 5px (эффект стекла). */
            overflow: hidden;
            /* Скрытие содержимого за пределами карточки. */
        }

        .cosmic-header {
            /* Переопределяем стили шапки для карточек. */
            background: linear-gradient(135deg, #6a0dad 0%, #4b0082 100%);
            /* Градиент: от фиолетового (#6a0dad) к тёмно-фиолетовому (#4b0082), угол 135°. */
            border-bottom: 1px solid #5a4a7a;
            /* Нижняя граница: 1px, светло-фиолетовая (#5a4a7a). */
            padding: 1.5rem;
            /* Отступы внутри: 1.5rem (24px). */
        }

        .cosmic-body {
            /* Стили для тела карточки. */
            padding: 2rem;
            /* Отступы внутри: 2rem (32px). */
            color: #e0e0e0;
            /* Цвет текста: светло-серый. */
        }

        .cosmic-spinner {
            /* Стили для спиннера (анимация загрузки). */
            animation: spin 1s linear infinite;
            /* Анимация "spin": 1 секунда, линейная, бесконечная. */
            font-size: 1.5rem;
            /* Размер: 1.5rem (24px). */
            color: #6a0dad;
            /* Цвет: фиолетовый (#6a0dad). */
        }

        @keyframes spin {
            /* Анимация вращения для спиннера. */
            0% { transform: rotate(0deg); }
            /* Начало: без поворота. */
            100% { transform: rotate(360deg); }
            /* Конец: полный поворот на 360°. */
        }

        .cosmic-filter-form {
            /* Стили для формы фильтрации. */
            background-color: rgba(30, 30, 40, 0.7);
            /* Полупрозрачный тёмный фон. */
            border: 1px solid #4a3a6a;
            /* Граница: 1px, фиолетовая (#4a3a6a). */
            border-radius: 8px;
            /* Скругление углов: 8px. */
            padding: 1.5rem;
            /* Отступы внутри: 1.5rem (24px). */
        }

        .cosmic-label {
            /* Стили для меток в формах. */
            display: block;
            /* Занимает всю ширину строки. */
            margin-bottom: 0.5rem;
            /* Отступ снизу: 0.5rem (8px). */
            color: #d0d0d0;
            /* Цвет: светло-серый (#d0d0d0). */
            font-weight: 500;
            /* Жирность шрифта: 500. */
        }

        .cosmic-input, .cosmic-select {
            /* Стили для полей ввода и выпадающих списков. */
            display: block;
            /* Занимает всю ширину. */
            width: 100%;
            /* Ширина 100%. */
            padding: 0.75rem 1rem;
            /* Отступы: 0.75rem (12px) сверху/снизу, 1rem (16px) по бокам. */
            background-color: rgba(25, 25, 35, 0.8);
            /* Полупрозрачный тёмный фон. */
            border: 1px solid #4a3a6a;
            /* Граница: 1px, фиолетовая (#4a3a6a). */
            border-radius: 6px;
            /* Скругление углов: 6px. */
            color: #ffffff;
            /* Цвет текста: белый. */
            font-size: 1rem;
            /* Размер шрифта: 1rem (16px). */
            transition: all 0.3s ease;
            /* Плавный переход для всех изменений за 0.3 секунды. */
        }

        .cosmic-input:focus, .cosmic-select:focus {
            /* Стили для полей при фокусе (клик или ввод). */
            outline: none;
            /* Убираем стандартный контур. */
            border-color: #8a6dbb;
            /* Граница: светло-фиолетовая (#8a6dbb). */
            box-shadow: 0 0 0 3px rgba(138, 109, 187, 0.2);
            /* Тень: размытие 3px, полупрозрачный фиолетовый. */
            background-color: rgba(30, 30, 40, 0.9);
            /* Чуть светлее фон при фокусе. */
        }

        .cosmic-filter-actions {
            /* Стили для контейнера кнопок в форме фильтрации. */
            display: flex;
            /* Flexbox для горизонтального расположения. */
            justify-content: center;
            /* Кнопки по центру. */
            gap: 1rem;
            /* Расстояние между кнопками: 1rem (16px). */
            margin-top: 1rem;
            /* Отступ сверху: 1rem (16px). */
            flex-wrap: wrap;
            /* Перенос кнопок на новую строку, если не помещаются. */
        }

        .cosmic-btn {
            /* Переопределяем стили кнопок для форм. */
            padding: 0.75rem 1.5rem;
            /* Отступы: 0.75rem (12px) сверху/снизу, 1.5rem (24px) по бокам. */
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            /* Центрирование содержимого по горизонтали. */
        }

        .cosmic-btn-primary {
            background-color: #6a0dad;
            /* Фон: тёмно-фиолетовый (#6a0dad). */
            color: white;
            border: none;
        }

        .cosmic-btn-primary:hover {
            background-color: #7b1de8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        }

        .cosmic-btn-outline {
            background-color: transparent;
            border: 1px solid #6a0dad;
            color: #b0a0d0;
            /* Цвет текста: светло-фиолетовый (#b0a0d0). */
        }

        .cosmic-btn-outline:hover {
            background-color: rgba(106, 13, 173, 0.1);
            color: #d0c0f0;
            transform: translateY(-2px);
        }

        .cosmic-alert {
            /* Стили для уведомлений. */
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            /* Отступ снизу: 1.5rem (24px). */
            display: flex;
            align-items: center;
        }

        .cosmic-alert-success {
            /* Уведомление об успехе. */
            background-color: rgba(40, 167, 69, 0.15);
            /* Полупрозрачный зелёный фон. */
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #a0e8b0;
            /* Цвет текста: светло-зелёный (#a0e8b0). */
        }

        .cosmic-alert-danger {
            /* Уведомление об ошибке. */
            background-color: rgba(220, 53, 69, 0.15);
            /* Полупрозрачный красный фон. */
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff9e9e;
            /* Цвет текста: светло-красный (#ff9e9e). */
        }

        .cosmic-alert-warning {
            /* Уведомление-предупреждение. */
            background-color: rgba(255, 193, 7, 0.15);
            /* Полупрозрачный жёлтый фон. */
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffeb99;
            /* Цвет текста: светло-жёлтый (#ffeb99). */
        }

        .cosmic-table-container {
            /* Стили для контейнера таблицы. */
            border-radius: 8px;
            overflow: hidden;
            /* Скрытие содержимого за пределами. */
        }

        .cosmic-table {
            /* Стили для таблицы. */
            width: 100%;
            border-collapse: separate;
            /* Ячейки разделены (не сливаются). */
            border-spacing: 0;
            /* Без промежутков между ячейками. */
            background-color: rgba(30, 30, 40, 0.7);
            /* Полупрозрачный тёмный фон. */
        }

        .cosmic-table th {
            /* Стили для заголовков таблицы. */
            background-color: rgba(74, 58, 106, 0.7);
            /* Полупрозрачный фиолетовый фон. */
            color: white;
            padding: 1rem;
            text-align: left;
            /* Выравнивание текста слева. */
            font-weight: 500;
        }

        .cosmic-table td {
            /* Стили для ячеек таблицы. */
            padding: 1rem;
            border-bottom: 1px solid rgba(74, 58, 106, 0.5);
            /* Нижняя граница: полупрозрачная фиолетовая. */
            vertical-align: middle;
            /* Выравнивание содержимого по центру по вертикали. */
        }

        .cosmic-table-row:hover {
            /* Стили для строк таблицы при наведении. */
            background-color: rgba(74, 58, 106, 0.3);
            /* Полупрозрачный фиолетовый фон. */
        }

        .cosmic-empty-state {
            /* Стили для пустой таблицы. */
            text-align: center;
            padding: 2rem;
            color: #a0a0a0;
            /* Цвет текста: серый (#a0a0a0). */
        }

        .cosmic-avatar {
            /* Стили для аватаров (фото). */
            width: 70px;
            height: 70px;
            border-radius: 50%;
            /* Круглая форма. */
            object-fit: cover;
            /* Изображение заполняет контейнер, сохраняя пропорции. */
            border: 2px solid #6a0dad;
            /* Граница: 2px, фиолетовая (#6a0dad). */
        }

        .cosmic-avatar-placeholder {
            /* Стили для заглушки аватара. */
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: rgba(74, 58, 106, 0.5);
            /* Полупрозрачный фиолетовый фон. */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0a0d0;
            /* Цвет текста: светло-фиолетовый (#b0a0d0). */
            font-size: 1.5rem;
            /* Размер шрифта: 1.5rem (24px). */
        }

        .cosmic-name {
            /* Стили для имён в таблице. */
            font-weight: 500;
            color: white;
        }

        .cosmic-date {
            /* Стили для дат в таблице. */
            color: #b0b0b0;
            /* Цвет: серый (#b0b0b0). */
        }

        .cosmic-badge {
            /* Стили для бейджей (например, название бригады). */
            display: inline-block;
            padding: 0.35rem 0.75rem;
            background-color: rgba(106, 13, 173, 0.3);
            /* Полупрозрачный фиолетовый фон. */
            border: 1px solid #6a0dad;
            border-radius: 20px;
            /* Сильно скруглённые углы. */
            color: #d0c0f0;
            font-size: 0.85rem;
            /* Размер шрифта: 0.85rem (14px). */
        }

        .cosmic-actions {
            /* Стили для контейнера кнопок действий. */
            display: flex;
            flex-direction: row;
            gap: 0.5rem;
            /* Расстояние между кнопками: 0.5rem (8px). */
            align-items: center;
        }

        .cosmic-action-btn {
            /* Общие стили для кнопок действий (просмотр, редактировать, удалить). */
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .cosmic-view {
            /* Стили для кнопки "Просмотр". */
            background-color: rgba(32, 201, 151, 0.1);
            /* Полупрозрачный зелёный фон. */
            color: #20c997;
            /* Цвет текста: зелёный (#20c997). */
            border: 1px solid rgba(32, 201, 151, 0.3);
        }

        .cosmic-view:hover {
            background-color: rgba(32, 201, 151, 0.2);
            color: #20c997;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(32, 201, 151, 0.1);
        }

        .cosmic-edit {
            /* Стили для кнопки "Редактировать". */
            background-color: rgba(255, 193, 7, 0.1);
            /* Полупрозрачный жёлтый фон. */
            color: #ffc107;
            /* Цвет текста: жёлтый (#ffc107). */
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .cosmic-edit:hover {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(255, 193, 7, 0.1);
        }

        .cosmic-delete {
            /* Стили для кнопки "Удалить". */
            background-color: rgba(220, 53, 69, 0.1);
            /* Полупрозрачный красный фон. */
            color: #dc3545;
            /* Цвет текста: красный (#dc3545). */
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .cosmic-delete:hover {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.1);
        }

        .cosmic-background {
            /* Стили для общего фона (не используется здесь явно). */
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .cosmic-background::before {
            /* Псевдоэлемент для текстуры фона. */
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/stardust.png');
            /* Текстура звёздного неба. */
            opacity: 0.1;
            z-index: -1;
            /* За основным контентом. */
        }
    </style>

    <!-- Стили для кастомного модального окна -->
    <style>
        .custom-modal {
            /* Стили для модального окна (всплывающее окно). */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* Полупрозрачный чёрный фон (затемнение). */
            z-index: 1050;
            /* Высокий слой, выше основного контента. */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .custom-modal-content {
            /* Стили для содержимого модального окна. */
            background-color: #2a2a3a;
            /* Тёмный фон (#2a2a3a). */
            border: 1px solid #6a0dad;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            /* Максимальная ширина: 500px. */
            color: #e0e0e0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .custom-modal-header {
            /* Стили для заголовка модального окна. */
            background: #6a0dad;
            padding: 1rem;
            border-bottom: 1px solid #5a4a7a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .custom-modal-title {
            /* Стили для текста заголовка. */
            margin: 0;
            color: white;
            font-size: 1.25rem;
            /* Размер шрифта: 1.25rem (20px). */
        }

        .custom-modal-close {
            /* Стили для кнопки закрытия. */
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .custom-modal-close:hover {
            color: #d0c0f0;
        }

        .custom-modal-body {
            /* Стили для тела модального окна. */
            padding: 1.5rem;
            text-align: center;
        }

        .custom-modal-body p {
            margin-bottom: 1rem;
            color: #e0e0e0;
        }

        .custom-export-btn {
            /* Стили для кнопки экспорта в модальном окне. */
            background-color: #6a0dad;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-export-btn:hover {
            background-color: #7b1de8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
        }

        .custom-modal-footer {
            /* Стили для нижней части модального окна. */
            border-top: 1px solid #5a4a7a;
            padding: 1rem;
            text-align: center;
        }

        .custom-modal-cancel {
            /* Стили для кнопки отмены. */
            background-color: transparent;
            border: 1px solid #6a0dad;
            color: #b0a0d0;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-modal-cancel:hover {
            background-color: rgba(106, 13, 173, 0.1);
            color: #d0c0f0;
            transform: translateY(-2px);
        }
    </style>
</head>
<!-- Закрываем <head>. Здесь заканчиваются метаданные, стили и подключения. -->

<body>
    <!-- Открываем <body> — здесь начинается видимое содержимое страницы. -->
    <!-- Контейнер для звёзд -->
    <div class="stars" id="stars-container"></div>
    <!-- Создаём <div> для звёзд с классом stars и ID stars-container. JavaScript будет заполнять его звёздами. -->

    <!-- Контейнер для метеоритов -->
    <div class="meteor-shower" id="meteor-shower-container"></div>
    <!-- Создаём <div> для метеоритов с классом meteor-shower и ID meteor-shower-container. -->

    <!-- Декоративный слой с градиентом -->
    <div class="gradient-overlay"></div>
    <!-- Создаём <div> для декоративного градиентного слоя. -->

    <!-- Шапка -->
    <header class="cosmic-header animate__animated animate__fadeIn">
        <!-- Открываем <header> — шапка страницы. -->
        <!-- Класс cosmic-header: стили из CSS. -->
        <!-- animate__animated animate__fadeIn: анимация появления (Animate.css). -->
        <div class="container">
            <!-- Контейнер Bootstrap для ограничения ширины и центрирования. -->
            <div class="header-content d-flex justify-content-between align-items-center">
                <!-- Контейнер для содержимого шапки: -->
                <!-- d-flex: flexbox (Bootstrap). -->
                <!-- justify-content-between: элементы по краям (Bootstrap). -->
                <!-- align-items-center: выравнивание по центру по вертикали (Bootstrap). -->
                <div class="header-left text-center">
                    <!-- Левая часть шапки: -->
                    <!-- text-center: центрирование текста (Bootstrap). -->
                    <i class="bi bi-people-fill text-purple me-2"></i>
                    <!-- Иконка группы людей из Bootstrap Icons. -->
                    <!-- text-purple: фиолетовый цвет из наших стилей. -->
                    <!-- me-2: отступ справа 2 единицы (Bootstrap). -->
                    <span class="header-title">БРИГАДА</span>
                    <!-- Заголовок "БРИГАДА" с классом header-title. -->
                    <span class="header-subtitle">Профессиональная команда для ваших задач</span>
                    <!-- Подзаголовок с классом header-subtitle. -->
                </div>
                <div class="header-right d-flex align-items-center justify-content-end">
                    <!-- Правая часть шапки: -->
                    <!-- d-flex, align-items-center, justify-content-end: flexbox с выравниванием справа (Bootstrap). -->
                    <a href="index.php" class="cosmic-btn cosmic-btn-outline">
                        <!-- Ссылка на главную страницу: -->
                        <!-- cosmic-btn, cosmic-btn-outline: стили кнопки с контуром. -->
                        <i class="bi bi-house-door me-1"></i> Главная
                        <!-- Иконка дома (Bootstrap Icons) и текст "Главная". -->
                        <!-- me-1: отступ справа 1 единица (Bootstrap). -->
                    </a>
                    <a href="add_person.php" class="cosmic-btn cosmic-btn-primary">
                        <!-- Ссылка на страницу добавления: -->
                        <!-- cosmic-btn, cosmic-btn-primary: стили основной кнопки. -->
                        <i class="bi bi-person-plus me-1"></i> Добавить
                        <!-- Иконка человека с плюсом и текст "Добавить". -->
                    </a>
                </div>
            </div>
        </div>
    </header>
    <!-- Закрываем <header>. Шапка содержит логотип, слоган и две кнопки. -->

    <!-- JavaScript для генерации звёзд и метеоритов -->
    <script>
    // Открываем тег <script> — начало JavaScript-кода, выполняемого в браузере.
    function getRandom(min, max) {
        // Функция для генерации случайного числа в диапазоне от min до max.
        return Math.random() * (max - min) + min;
        // Math.random(): случайное число от 0 до 1.
        // (max - min): диапазон значений.
        // + min: смещение до минимального значения.
    }

    function generateStars() {
        // Функция для генерации звёзд.
        const starsContainer = document.getElementById('stars-container');
        // Находим контейнер для звёзд по ID.
        if (!starsContainer) {
            console.error('Контейнер для звёзд не найден');
            // Если контейнер не найден, выводим ошибку в консоль и выходим.
            return;
        }
        const numStars = 150;
        // Количество звёзд: 150.

        for (let i = 0; i < numStars; i++) {
            // Цикл для создания 150 звёзд.
            const star = document.createElement('div');
            // Создаём новый элемент <div> для звезды.
            star.className = 'star';
            // Задаём класс star для применения стилей.
            const size = getRandom(1, 3);
            // Случайный размер звезды: от 1 до 3px.
            const left = getRandom(0, 100);
            // Случайная позиция слева: от 0 до 100%.
            const top = getRandom(0, 100);
            // Случайная позиция сверху: от 0 до 100%.
            const delay = getRandom(0, 5);
            // Случайная задержка анимации: от 0 до 5 секунд.
            const duration = getRandom(3, 8);
            // Случайная длительность анимации: от 3 до 8 секунд.
            const color = getRandom(0, 100) > 70 ? 'purple' : 'white';
            // Случайный цвет: фиолетовый, если > 70 (30% шанс), иначе белый.

            star.style.width = `${size}px`;
            // Устанавливаем ширину звезды.
            star.style.height = `${size}px`;
            // Устанавливаем высоту звезды.
            star.style.left = `${left}%`;
            // Позиция слева в процентах.
            star.style.top = `${top}%`;
            // Позиция сверху в процентах.
            star.style.animationDelay = `${delay}s`;
            // Задержка анимации в секундах.
            star.style.animationDuration = `${duration}s`;
            // Длительность анимации в секундах.
            star.style.backgroundColor = color === 'purple' ? 'var(--purple-accent)' : '#fff';
            // Цвет фона: фиолетовый (#c77dff) или белый.
            star.style.boxShadow = color === 'purple' 
                ? '0 0 10px 2px var(--purple-accent)' 
                : '0 0 10px 1px #fff';
            // Тень: для фиолетовых больше размытие (10px, 2px), для белых меньше (10px, 1px).

            starsContainer.appendChild(star);
            // Добавляем звезду в контейнер.
        }
    }

    function generateMeteors() {
        // Функция для генерации метеоритов.
        const meteorsContainer = document.getElementById('meteor-shower-container');
        if (!meteorsContainer) {
            console.error('Контейнер для метеоритов не найден');
            return;
        }
        let numMeteorsOnScreen = 0;
        // Счётчик текущих метеоритов на экране.
        const maxMeteorsOnScreen = 15;
        // Максимум метеоритов одновременно: 15.
        const meteorInterval = 5000;
        // Интервал появления новых метеоритов: 5000 мс (5 секунд).

        function createMeteor() {
            // Внутренняя функция для создания одного метеорита.
            if (numMeteorsOnScreen >= maxMeteorsOnScreen) return;
            // Если метеоритов уже максимум, ничего не делаем.

            const meteor = document.createElement('div');
            meteor.className = 'meteor';
            const left = getRandom(0, 100);
            // Случайная позиция слева: 0–100%.
            const top = getRandom(-50, 20);
            // Случайная позиция сверху: от -50px (за экраном) до 20px.
            const duration = getRandom(1, 2);
            // Случайная длительность падения: 1–2 секунды.

            meteor.style.left = `${left}%`;
            meteor.style.top = `${top}px`;
            meteor.style.animationDuration = `${duration}s`;

            numMeteorsOnScreen++;
            // Увеличиваем счётчик метеоритов.
            meteor.addEventListener('animationend', () => {
                // Слушатель события окончания анимации.
                meteor.remove();
                // Удаляем метеорит из DOM.
                numMeteorsOnScreen--;
                // Уменьшаем счётчик.
            });

            meteorsContainer.appendChild(meteor);
            // Добавляем метеорит в контейнер.
        }

        const numInitialMeteors = 5;
        // Начальное количество метеоритов: 5.
        for (let i = 0; i < numInitialMeteors; i++) {
            setTimeout(createMeteor, getRandom(0, 5000));
            // Создаём 5 метеоритов с случайной задержкой 0–5 секунд.
        }
        setInterval(createMeteor, meteorInterval);
        // Запускаем создание метеоритов каждые 5 секунд.
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Слушатель события загрузки DOM.
        generateStars();
        // Генерируем звёзды.
        generateMeteors();
        // Генерируем метеориты.
    });
    </script>
    <!-- Закрываем <script>. JavaScript добавляет анимации звёзд и метеоритов. -->

    <!-- Основное содержимое страницы -->
    <main class="container my-4 animate__animated animate__fadeIn">
    <!-- Открываем <main> — основное содержимое. -->
    <!-- container: ограничение ширины (Bootstrap). -->
    <!-- my-4: вертикальные отступы 4 единицы (Bootstrap). -->
    <!-- animate__animated animate__fadeIn: анимация появления. -->