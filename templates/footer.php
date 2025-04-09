<?php
// Это открывающий тег PHP — начало серверного кода.
declare(strict_types=1);
// Включаем строгую типизацию — PHP будет проверять, чтобы типы данных (например, строка, число) в функциях строго соответствовали указанным. Это помогает избежать ошибок.
?>
    </main>
    <!-- Закрываем тег <main>. Он был открыт ранее (например, в header.php) и содержит основное содержимое страницы (таблицы, формы и т.д.). Теперь страница переходит к подвалу. -->
    
    <!-- Подвал -->
    <footer class="bg-dark text-secondary py-4 border-top border-dark animate__animated animate__fadeIn">
        <!-- Открываем тег <footer> — это подвал сайта. Он содержит дополнительную информацию, например, копирайт и ссылки. -->
        <!-- Классы: -->
        <!-- bg-dark: тёмный фон (Bootstrap). -->
        <!-- text-secondary: серый цвет текста (Bootstrap). -->
        <!-- py-4: вертикальные отступы внутри (padding) на 4 единицы (Bootstrap). -->
        <!-- border-top: верхняя граница (Bootstrap). -->
        <!-- border-dark: тёмный цвет границы (Bootstrap). -->
        <!-- animate__animated animate__fadeIn: анимация плавного появления из Animate.css. -->
        <div class="container">
            <!-- Создаём контейнер с классом container (Bootstrap). Он ограничивает ширину содержимого и центрирует его на странице. -->
            <div class="row">
                <!-- Создаём строку с классом row (Bootstrap). Это часть сетки, чтобы разделить подвал на колонки. -->
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <!-- Первая колонка: -->
                    <!-- col-md-6: занимает половину ширины (6 из 12 колонок) на средних и больших экранах (Bootstrap). -->
                    <!-- text-center: центрирует текст на маленьких экранах (Bootstrap). -->
                    <!-- text-md-start: выравнивает текст слева на средних и больших экранах (Bootstrap). -->
                    <!-- mb-3: нижний отступ 3 единицы на маленьких экранах (Bootstrap). -->
                    <!-- mb-md-0: убирает нижний отступ на средних и больших экранах (Bootstrap). -->
                    <h5 class="text-uppercase">БРИГАДА</h5>
                    <!-- Заголовок пятого уровня <h5> с классом text-uppercase (Bootstrap), который делает текст заглавным: "БРИГАДА". -->
                    <p class="mb-0">Профессиональная команда для ваших задач</p>
                    <!-- Параграф <p> с текстом описания. -->
                    <!-- mb-0: убирает нижний отступ (Bootstrap), чтобы текст примыкал к заголовку. -->
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <!-- Вторая колонка: -->
                    <!-- col-md-6: занимает вторую половину ширины на средних и больших экранах (Bootstrap). -->
                    <!-- text-center: центрирует текст на маленьких экранах (Bootstrap). -->
                    <!-- text-md-end: выравнивает текст справа на средних и больших экранах (Bootstrap). -->
                    <div class="social-icons mb-3">
                        <!-- Контейнер для иконок социальных сетей. -->
                        <!-- mb-3: нижний отступ 3 единицы (Bootstrap). -->
                        <a href="#" class="text-secondary me-3"><i class="bi bi-telegram fs-4"></i></a>
                        <!-- Ссылка на Telegram (href="#" — заглушка): -->
                        <!-- text-secondary: серый цвет текста (Bootstrap). -->
                        <!-- me-3: правый отступ 3 единицы (Bootstrap). -->
                        <!-- <i class="bi bi-telegram fs-4"></i>: иконка Telegram из Bootstrap Icons, размер fs-4 (крупный). -->
                        <a href="#" class="text-secondary me-3"><i class="bi bi-whatsapp fs-4"></i></a>
                        <!-- Ссылка на WhatsApp: -->
                        <!-- text-secondary: серый цвет. -->
                        <!-- me-3: правый отступ. -->
                        <!-- <i class="bi bi-whatsapp fs-4"></i>: иконка WhatsApp. -->
                        <a href="#" class="text-secondary"><i class="bi bi-github fs-4"></i></a>
                        <!-- Ссылка на GitHub: -->
                        <!-- text-secondary: серый цвет. -->
                        <!-- Без me-3, так как это последняя иконка. -->
                        <!-- <i class="bi bi-github fs-4"></i>: иконка GitHub. -->
                    </div>
                    <p class="mb-0">
                        <!-- Параграф для копирайта: -->
                        <!-- mb-0: убирает нижний отступ (Bootstrap). -->
                        <i class="bi bi-c-circle"></i> <?= date('Y'); ?> БРИГАДА. Все права защищены.
                        <!-- <i class="bi bi-c-circle"></i>: иконка копирайта из Bootstrap Icons. -->
                        <!-- <?= date('Y'); ?>: PHP-код, выводит текущий год (например, 2025). -->
                        <!-- "БРИГАДА. Все права защищены." — статический текст. -->
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Закрываем <footer>. Подвал разделён на две колонки: слева — название и слоган, справа — соцсети и копирайт. -->

    <!-- Стили для футера -->
    <style>
        /* CSS-стили внутри тега <style>. Они переопределяют или дополняют стили из header.php. */
        footer {
            /* Стили для тега <footer>: */
            flex-shrink: 0;
            /* flex-shrink: 0 — подвал не будет сжиматься, даже если содержимое страницы большое (часть flexbox). */
            background: linear-gradient(to top, var(--secondary-bg-color), var(--main-bg-color));
            /* Градиент фона: снизу var(--secondary-bg-color) (#0d0413) кверху var(--main-bg-color) (#1a0525). */
            border-top: 2px solid var(--purple-accent);
            /* Верхняя граница: толщина 2px, сплошная, цвет var(--purple-accent) (#c77dff). */
            padding: 2rem 0;
            /* Внутренние отступы: 2rem (32px) сверху и снизу, 0 по бокам. */
            color: var(--text-color);
            /* Цвет текста: var(--text-color) (#e0e0e0). */
            margin-top: 2rem;
            /* Внешний отступ сверху: 2rem (32px), чтобы отделить подвал от содержимого <main>. */
        }

        footer h5 {
            /* Стили для заголовков <h5> в подвале: */
            font-family: 'Orbitron', sans-serif;
            /* Шрифт Orbitron (футуристический стиль) с запасным вариантом sans-serif. */
            color: var(--purple-accent);
            /* Цвет текста: var(--purple-accent) (#c77dff). */
            text-shadow: 0 0 9px var(--purple-accent);
            /* Тень текста: горизонтальное смещение 0, вертикальное 0, размытие 9px, цвет var(--purple-accent). Создаёт эффект свечения. */
        }

        footer p {
            /* Стили для параграфов <p> в подвале: */
            color: var(--text-color);
            /* Цвет текста: var(--text-color) (#e0e0e0). */
            font-size: 1.2rem;
            /* Размер шрифта: 1.2rem (примерно 19px), чтобы текст был чуть крупнее стандартного. */
        }
    </style>

    <script>
        // Открываем тег <script> — начало JavaScript-кода, который выполняется в браузере.
        // Анимация при наведении на элементы с классом hover-effect
        document.querySelectorAll('.hover-effect').forEach(element => {
            // document.querySelectorAll('.hover-effect'): находит все элементы с классом hover-effect (например, фото или кнопки).
            // forEach(element => { ... }): перебирает каждый найденный элемент, называя его element.
            element.addEventListener('mouseenter', () => {
                // element.addEventListener('mouseenter', ...): добавляет реакцию на событие mouseenter (курсор наводится на элемент).
                element.classList.add('animate__pulse');
                // element.classList.add('animate__pulse'): добавляет класс animate__pulse из Animate.css, чтобы элемент "пульсировал" при наведении.
            });
            element.addEventListener('mouseleave', () => {
                // element.addEventListener('mouseleave', ...): добавляет реакцию на событие mouseleave (курсор покидает элемент).
                element.classList.remove('animate__pulse');
                // element.classList.remove('animate__pulse'): убирает класс animate__pulse, останавливая анимацию.
                // Примечание: без дополнительных настроек анимация может не повторяться, так как Animate.css требует сброса.
            });
        });

        // Анимация для социальных иконок в подвале
        document.querySelectorAll('.social-icons a').forEach(icon => {
            // document.querySelectorAll('.social-icons a'): находит все ссылки <a> внутри элемента с классом social-icons.
            // forEach(icon => { ... }): перебирает каждую ссылку, называя её icon.
            icon.addEventListener('mouseenter', () => {
                // Добавляем реакцию на наведение курсора.
                icon.style.transform = 'scale(1.3) rotate(10deg)';
                // icon.style.transform: изменяет CSS-свойство transform элемента:
                // scale(1.3): увеличивает иконку на 30%.
                // rotate(10deg): поворачивает иконку на 10 градусов по часовой стрелке.
            });
            icon.addEventListener('mouseleave', () => {
                // Добавляем реакцию на уход курсора.
                icon.style.transform = '';
                // Сбрасываем transform, возвращая иконку в исходное состояние (без увеличения и поворота).
            });
        });
    </script>
    <!-- Закрываем тег <script>. JavaScript-код добавляет интерактивные анимации для элементов страницы. -->
</body>
<!-- Закрываем тег <body>. Здесь заканчивается всё видимое содержимое страницы (шапка, основное содержимое, подвал и скрипты). -->
</html>
<!-- Закрываем тег <html>. Это завершает весь HTML-документ, начатый в header.php. -->
<?php
// Снова открываем PHP-блок для завершения серверной обработки.
if (ob_get_level() > 0) {
    // ob_get_level(): возвращает уровень вложенности буферизации вывода (0, если она не активна).
    // Проверяем, включена ли буферизация (например, через ob_start() в header.php).
    ob_end_flush();
    // ob_end_flush(): завершает буферизацию и отправляет всё накопленное содержимое (HTML, текст) в браузер.
}
// Закрываем PHP-блок. Серверная часть завершена, страница полностью сформирована и отправлена пользователю.
?>