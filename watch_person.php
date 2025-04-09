<?php
// Это открывающий тег PHP — начало кода на PHP.

// Включаем строгую типизацию — заставляем PHP строго проверять типы данных (например, строка, число и т.д.).
declare(strict_types=1);

// Начинаем буферизацию вывода — всё, что скрипт выводит (HTML, ошибки), сначала сохраняется в памяти, а не сразу отправляется в браузер.
ob_start();

// Подключаем файл logic.php из той же папки — он содержит классы для работы с базой данных (например, PersonRepository).
require_once __DIR__ . '/logic.php';

// Подключаем файл header.php из папки templates — это верхняя часть сайта (HTML-код с меню, стилями и т.д.).
require_once __DIR__ . '/templates/header.php';

// Подключаем класс PersonRepository из пространства имён Repository, чтобы использовать его короче.
use Repository\PersonRepository;

// Создаём переменную $person и задаём ей значение null — она будет содержать данные о человеке, если он найден.
$person = null;

// Создаём переменную $error как пустую строку — сюда запишем сообщение об ошибке, если что-то пойдёт не так.
$error = '';

// Начинаем блок try-catch для обработки ошибок, чтобы скрипт не сломался.
try {
    // Проверяем, есть ли параметр 'id' в URL (например, watch_person.php?id=5) и является ли он числом.
    if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) { // $_GET — данные из URL.
        // Если 'id' нет или это не число (например, буквы), выбрасываем ошибку.
        throw new InvalidArgumentException("Неверный идентификатор");
    }

    // Преобразуем 'id' из строки в число, чтобы использовать его в запросах к базе.
    $id = (int)$_GET['id'];

    // Создаём объект PersonRepository, передавая ему подключение к базе данных из класса Database.
    $personRepository = new PersonRepository(\Repository\Database::getConnection());

    // Пробуем найти человека в базе по его ID.
    $person = $personRepository->getPersonById($id);

    // Если человек не найден (метод вернул null), выбрасываем ошибку.
    if (!$person) {
        throw new RuntimeException("Человек не найден");
    }

    // Проверяем, есть ли в URL параметр 'delete' (например, watch_person.php?id=5&delete=1).
    if (isset($_GET['delete'])) {
        // Пробуем удалить человека из базы по его ID.
        if ($personRepository->deletePerson($id)) {
            ob_end_clean(); // Очищаем буфер вывода перед перенаправлением.
            // Перенаправляем на главную страницу с параметрами успеха.
            header('Location: index.php?success=1&action=delete');
            exit; // Завершаем скрипт, чтобы ничего лишнего не выполнилось.
        } else {
            // Если удаление не удалось, выбрасываем ошибку.
            throw new RuntimeException("Не удалось удалить запись");
        }
    }

} catch (InvalidArgumentException $e) { // Ловим ошибку некорректного ID.
    ob_end_clean(); // Очищаем буфер.
    header("Location: index.php"); // Перенаправляем на главную страницу без сообщения.
    exit; // Завершаем скрипт.
} catch (RuntimeException $e) { // Ловим ошибки выполнения (например, человек не найден).
    $error = $e->getMessage(); // Сохраняем сообщение об ошибке для показа.
} catch (Throwable $e) { // Ловим любые другие ошибки (например, проблемы с базой).
    error_log('Error in watch_person.php: ' . $e->getMessage()); // Записываем ошибку в лог-файл сервера.
    $error = 'Произошла непредвиденная ошибка'; // Показываем пользователю общее сообщение.
}
?>

<!-- Начинаем HTML-код страницы -->
<div class="container mt-4 animate__animated animate__fadeIn">
    <!-- Контейнер Bootstrap с отступом сверху (mt-4) и анимацией появления (fadeIn) -->

    <?php if ($error): ?>
        <!-- Если есть ошибка, показываем её -->
        <div class="alert cosmic-alert cosmic-alert-danger">
            <!-- Уведомление с красным фоном -->
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <!-- Иконка предупреждения с отступом справа (me-2) -->
            <?= htmlspecialchars($error) ?>
            <!-- Текст ошибки, защищённый от XSS (htmlspecialchars преобразует специальные символы в безопасные) -->
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <!-- Кнопка закрытия уведомления (работает с Bootstrap JS) -->
        </div>
    <?php endif; ?>

    <?php if ($person): ?>
    <!-- Если данные о человеке найдены, показываем карточку -->
    <div class="card cosmic-card">
        <!-- Карточка с информацией -->
        <div class="card-header cosmic-header">
            <!-- Заголовок карточки -->
            <h2 class="card-title mb-0 text-white">
                <!-- Заголовок без отступа снизу (mb-0) и белым текстом -->
                <i class="bi bi-person-badge"></i> 
                <!-- Иконка человека с бейджем -->
                <?= htmlspecialchars($person['full_name']) ?>
                <!-- Имя человека, защищённое от XSS -->
                <span class="cosmic-badge ms-2">
                    <!-- Бейдж с названием бригады и отступом слева (ms-2) -->
                    <?= htmlspecialchars($person['brigade_name'] ?? 'N/A') ?>
                    <!-- Название бригады или "N/A", если его нет -->
                </span>
            </h2>
        </div>
        
        <div class="card-body cosmic-body">
            <!-- Тело карточки -->
            <div class="row">
                <!-- Строка с двумя колонками -->
                <div class="col-md-4 text-center">
                    <!-- Левая колонка (4/12 ширины, центрированный текст) -->
                    <?php if (!empty($person['photo']) && file_exists($person['photo'])): ?>
                        <!-- Если есть путь к фото и файл существует -->
                        <img src="<?= htmlspecialchars($person['photo']) ?>" 
                             alt="Фото <?= htmlspecialchars($person['full_name']) ?>" 
                             class="cosmic-avatar mb-3 hover-effect">
                        <!-- Фото с экранированным путём, описанием и стилями (отступ снизу mb-3, эффект при наведении) -->
                    <?php else: ?>
                        <!-- Если фото нет или файл отсутствует -->
                        <div class="cosmic-avatar-placeholder mb-3 d-flex align-items-center justify-content-center hover-effect">
                            <!-- Заглушка с отступом снизу, центрированием и эффектом -->
                            <i class="bi bi-person-fill text-purple"></i>
                            <!-- Иконка человека с фиолетовым цветом -->
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-8">
                    <!-- Правая колонка (8/12 ширины) -->
                    <div class="mb-3">
                        <!-- Блок с отступом снизу -->
                        <h5 class="text-white-50">Основная информация</h5>
                        <!-- Подзаголовок светло-серого цвета -->
                        <hr class="border-purple">
                        <!-- Линия-разделитель фиолетового цвета -->
                        
                        <div class="row">
                            <!-- Внутренняя строка -->
                            <div class="col-sm-6 mb-3">
                                <!-- Половина ширины с отступом снизу -->
                                <p class="text-white">
                                    <strong><i class="bi bi-people-fill text-purple"></i> Бригада:</strong>
                                    <!-- Текст "Бригада" с иконкой -->
                                </p>
                                <?php if (!empty($person['brigade_name'])): ?>
                                    <span class="cosmic-badge fs-6">
                                        <?= htmlspecialchars($person['brigade_name']) ?>
                                        <!-- Название бригады в бейдже с размером текста fs-6 -->
                                    </span>
                                <?php else: ?>
                                    <p class="text-muted">Не указана</p>
                                    <!-- Сообщение серого цвета, если бригады нет -->
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-sm-6 mb-3">
                                <!-- Вторая половина ширины -->
                                <p class="text-white">
                                    <strong><i class="bi bi-calendar-date text-purple"></i> Дата рождения:</strong>
                                    <!-- Текст "Дата рождения" с иконкой -->
                                </p>
                                <p class="text-white">
                                    <?= !empty($person['date_of_birth']) 
                                        ? date('d.m.Y', strtotime($person['date_of_birth'])) 
                                        : '<span class="text-muted">Не указана</span>' ?>
                                    <!-- Дата в формате дд.мм.гггг или сообщение, если не указана -->
                                </p>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <!-- Строка с отступом сверху -->
                            <div class="col-12">
                                <!-- Полная ширина -->
                                <p class="text-white">
                                    <strong><i class="bi bi-clock-history text-purple"></i> Дата добавления:</strong>
                                    <!-- Текст "Дата добавления" с иконкой -->
                                </p>
                                <p class="text-white">
                                    <?= !empty($person['created_at']) && $person['created_at'] !== null 
                                        ? date('d.m.Y H:i', strtotime($person['created_at'])) 
                                        : '<span class="text-muted">Неизвестно</span>' ?>
                                    <!-- Дата и время создания или сообщение -->
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer cosmic-form-actions">
            <!-- Нижняя часть карточки с кнопками -->
            <div class="d-flex justify-content-between">
                <!-- Flex-контейнер для распределения кнопок по краям -->
                <a href="index.php" class="cosmic-btn cosmic-btn-outline">
                    <i class="bi bi-arrow-left me-2"></i> Назад к списку
                    <!-- Кнопка "Назад" с иконкой и отступом справа -->
                </a>
                
                <div class="button-group">
                    <!-- Группа кнопок "Редактировать" и "Удалить" -->
                    <a href="edit_person.php?id=<?= $person['id'] ?>" class="cosmic-btn btn cosmic-edit">
                        <i class="bi bi-pencil"></i> Редактировать
                        <!-- Кнопка редактирования с иконкой -->
                    </a>
                    <a href="watch_person.php?id=<?= $person['id'] ?>&delete=1" 
                       class="cosmic-btn btn cosmic-delete"
                       onclick="return confirm('Вы действительно хотите удалить <?= htmlspecialchars(addslashes($person['full_name'])) ?>?')">
                        <i class="bi bi-trash"></i> Удалить
                        <!-- Кнопка удаления с подтверждением (addslashes экранирует кавычки) -->
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Стили для страницы -->
<style>
/* Стили для карточки с информацией о человеке */
.cosmic-card {
    background-color: rgba(40, 40, 50, 0.95); /* Полупрозрачный тёмный фон */
    border: 1px solid #6a0dad; /* Фиолетовая граница */
    border-radius: 12px; /* Скруглённые углы */
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5); /* Тень для эффекта глубины */
    backdrop-filter: blur(3px); /* Размытие фона за карточкой */
    overflow: hidden; /* Скрытие содержимого за пределами */
    transition: transform 0.3s ease; /* Плавная анимация при наведении */
}

/* Эффект при наведении на карточку */
.cosmic-card:hover {
    transform: translateY(-3px); /* Поднимаем карточку на 3 пикселя вверх */
    box-shadow: 0 10px 25px rgba(106, 13, 173, 0.3); /* Усиливаем тень с фиолетовым оттенком */
}

/* Стили для заголовка карточки */
.cosmic-header {
    background: linear-gradient(135deg, #7b1de8 0%, #4b0082 100%); /* Градиент от светлого к тёмному фиолетовому */
    border-bottom: 2px solid #9c4dff; /* Фиолетовая линия снизу */
    padding: 1.5rem; /* Внутренние отступы */
}

/* Стили текста заголовка */
.cosmic-header h2 {
    color: #ffffff; /* Белый цвет текста */
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3); /* Тень текста для глубины */
}

/* Стили тела карточки */
.cosmic-body {
    padding: 2rem; /* Внутренние отступы */
    color: #f0f0f0; /* Светлый цвет текста по умолчанию */
    background-color: rgba(40, 40, 50, 0.95); /* Полупрозрачный тёмный фон */
}

/* Убеждаемся, что весь текст в теле карточки белый */
.cosmic-body p, .cosmic-body h5, .cosmic-body span {
    color: #ffffff !important; /* Принудительно белый цвет для всех текстовых элементов */
}

/* Стили аватара (фото) */
.cosmic-avatar {
    width: 200px; /* Ширина 200 пикселей */
    height: 200px; /* Высота 200 пикселей */
    border-radius: 50%; /* Круглая форма */
    object-fit: cover; /* Картинка заполняет контейнер, сохраняя пропорции */
    border: 3px solid #9c4dff; /* Фиолетовая граница */
    box-shadow: 0 0 10px rgba(156, 39, 176, 0.5); /* Фиолетовая тень */
    transition: transform 0.3s ease; /* Плавная анимация при наведении */
}

/* Эффект при наведении на аватар */
.cosmic-avatar:hover {
    transform: scale(1.05); /* Увеличиваем на 5% */
}

/* Стили для заглушки аватара */
.cosmic-avatar-placeholder {
    width: 200px; /* Ширина */
    height: 200px; /* Высота */
    border-radius: 50%; /* Круглая форма */
    background-color: rgba(90, 70, 120, 0.7); /* Полупрозрачный фиолетовый фон */
    display: flex; /* Flex для центрирования содержимого */
    align-items: center; /* Центрирование по вертикали */
    justify-content: center; /* Центрирование по горизонтали */
    color: #d0c0f0; /* Светло-фиолетовый цвет иконки */
    font-size: 3rem; /* Большой размер иконки */
    border: 3px solid #9c4dff; /* Фиолетовая граница */
}

/* Стили бейджа (например, название бригады) */
.cosmic-badge {
    display: inline-block; /* Бейдж занимает только нужное место */
    padding: 0.4rem 0.9rem; /* Внутренние отступы */
    background-color: #7b1de8; /* Фиолетовый фон */
    border: 1px solid #9c4dff; /* Фиолетовая граница */
    border-radius: 20px; /* Скруглённые углы */
    color: #ffffff; /* Белый текст */
    font-size: 0.9rem; /* Размер текста */
    font-weight: 500; /* Полужирный шрифт */
    box-shadow: 0 2px 5px rgba(106, 13, 173, 0.3); /* Лёгкая тень */
}

/* Стили группы формы (здесь не используется, но оставлено для совместимости) */
.cosmic-form-group {
    margin-bottom: 1.5rem; /* Отступ снизу */
}

/* Стили меток формы */
.cosmic-label {
    display: block; /* Метка занимает всю ширину */
    margin-bottom: 0.5rem; /* Отступ снизу */
    color: #d0d0d0; /* Светло-серый цвет */
    font-weight: 500; /* Полужирный шрифт */
    font-size: 1rem; /* Размер текста */
}

/* Стили полей ввода и выпадающих списков */
.cosmic-input, .cosmic-select {
    display: block; /* Поле на всю ширину */
    width: 100%; /* Ширина 100% */
    padding: 0.75rem 1rem; /* Внутренние отступы */
    background-color: rgba(50, 50, 60, 0.9); /* Тёмный фон */
    border: 1px solid #6a0dad; /* Фиолетовая граница */
    border-radius: 6px; /* Скруглённые углы */
    color: #ffffff; /* Белый текст */
    font-size: 1rem; /* Размер текста */
    transition: all 0.3s ease; /* Плавная анимация изменений */
}

/* Стили полей при фокусе */
.cosmic-input:focus, .cosmic-select:focus {
    outline: none; /* Убираем стандартный контур */
    border-color: #9c4dff; /* Фиолетовая граница */
    box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.3); /* Фиолетовая тень */
    background-color: rgba(60, 60, 70, 0.9); /* Чуть светлее фон */
}

/* Стили контейнера выбора файла */
.cosmic-file-input {
    position: relative; /* Для позиционирования дочерних элементов */
    overflow: hidden; /* Скрытие выходящего содержимого */
}

/* Стили поля выбора файла */
.cosmic-file {
    position: absolute; /* Абсолютное позиционирование */
    left: 0; /* Привязка к левому краю */
    top: 0; /* Привязка к верхнему краю */
    opacity: 0; /* Поле скрыто */
    width: 100%; /* Ширина 100% */
    height: 100%; /* Высота 100% */
    cursor: pointer; /* Курсор-указатель */
}

/* Стили метки выбора файла */
.cosmic-file-label {
    display: flex; /* Flex для центрирования */
    align-items: center; /* Центрирование по вертикали */
    padding: 0.75rem 1rem; /* Внутренние отступы */
    background-color: rgba(50, 50, 60, 0.9); /* Тёмный фон */
    border: 1px dashed #9c4dff; /* Пунктирная граница */
    border-radius: 6px; /* Скруглённые углы */
    color: #d0c0f0; /* Светло-фиолетовый текст */
    transition: all 0.3s ease; /* Плавная анимация */
}

/* Эффект при наведении на метку файла */
.cosmic-file-label:hover {
    border-color: #c77dff; /* Светлее граница */
    background-color: rgba(60, 60, 70, 0.9); /* Чуть светлее фон */
    color: #ffffff; /* Белый текст */
}

/* Стили текста внутри метки файла */
.cosmic-file-text {
    margin-left: 0.5rem; /* Отступ слева */
}

/* Стили подсказки под полем */
.cosmic-form-text {
    margin-top: 0.5rem; /* Отступ сверху */
    font-size: 0.875rem; /* Меньший размер текста */
    color: #b0b0b0; /* Серый цвет */
}

/* Стили контейнера кнопок действий */
.cosmic-form-actions {
    display: flex; /* Flex для расположения кнопок */
    justify-content: space-between; /* Распределение по краям */
    margin-top: 2rem; /* Отступ сверху */
    padding-top: 1.5rem; /* Внутренний отступ сверху */
    border-top: 1px solid #4a3a6a; /* Тонкая фиолетовая линия сверху */
    background-color: rgba(30, 30, 40, 0.9); /* Тёмный фон */
}

/* Стили для flex-контейнера внутри действий */
.cosmic-form-actions .d-flex {
    gap: 1rem !important; /* Отступ между "Назад" и группой кнопок */
}

/* Стили группы кнопок "Редактировать" и "Удалить" */
.button-group {
    display: flex !important; /* Flex для горизонтального расположения */
    gap: 1rem !important; /* Отступ между кнопками */
    align-items: center !important; /* Центрирование по вертикали */
}

/* Переопределяем стили Bootstrap для кнопок в группе */
.button-group .btn.cosmic-btn {
    margin: 0 !important; /* Убираем стандартный отступ */
    margin-right: 1rem !important; /* Добавляем отступ справа */
}

/* Убираем margin у последней кнопки в группе */
.button-group .btn.cosmic-btn:last-child {
    margin-right: 0 !important; /* Убираем отступ у последней кнопки */
}

/* Адаптивность для маленьких экранов (мобильных) */
@media (max-width: 576px) {
    .cosmic-form-actions .d-flex {
        gap: 0.5rem !important; /* Уменьшаем отступ между элементами */
    }
    .button-group {
        gap: 0.5rem !important; /* Уменьшаем отступ между кнопками */
    }
    .button-group .btn.cosmic-btn {
        margin-right: 0.5rem !important; /* Уменьшаем отступ справа */
    }
    .button-group .btn.cosmic-btn:last-child {
        margin-right: 0 !important; /* Убираем отступ у последней кнопки */
    }
}

/* Общие стили для всех кнопок */
.cosmic-btn {
    display: inline-flex !important; /* Flex для центрирования содержимого */
    align-items: center !important; /* Центрирование по вертикали */
    padding: 0.75rem 1.5rem !important; /* Внутренние отступы */
    border-radius: 8px !important; /* Скруглённые углы */
    font-weight: 600 !important; /* Полужирный шрифт */
    transition: all 0.3s ease !important; /* Плавная анимация изменений */
    border: none !important; /* Без границы */
    cursor: pointer !important; /* Курсор-указатель */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important; /* Лёгкая тень */
    text-decoration: none !important; /* Убираем подчёркивание у ссылок */
}

/* Стили кнопки с контуром (например, "Назад") */
.cosmic-btn-outline {
    background-color: transparent !important; /* Прозрачный фон */
    border: 2px solid #9c4dff !important; /* Фиолетовая граница */
    color: #c77dff !important; /* Светло-фиолетовый текст */
}

/* Эффект при наведении на кнопку с контуром */
.cosmic-btn-outline:hover {
    background-color: #9c4dff !important; /* Фиолетовый фон */
    color: #ffffff !important; /* Белый текст */
    transform: scale(1.05) !important; /* Увеличение на 5% */
    box-shadow: 0 4px 12px rgba(156, 39, 176, 0.4) !important; /* Фиолетовая тень */
}

/* Стили основной кнопки (для совместимости, здесь не используется) */
.cosmic-btn-primary {
    background-color: #9c4dff !important; /* Фиолетовый фон */
    color: #ffffff !important; /* Белый текст */
}

/* Эффект при наведении на основную кнопку */
.cosmic-btn-primary:hover {
    background-color: #c77dff !important; /* Светлее фиолетовый */
    transform: scale(1.05) !important; /* Увеличение */
    box-shadow: 0 4px 12px rgba(199, 125, 255, 0.5) !important; /* Фиолетовая тень */
}

/* Общие стили для кнопок действий */
.cosmic-view, .cosmic-edit, .cosmic-delete {
    padding: 0.75rem 1.25rem !important; /* Внутренние отступы */
    font-size: 1rem !important; /* Размер текста */
}

/* Стили кнопки просмотра (здесь не используется, но для полноты) */
.cosmic-view {
    background-color: #20c997 !important; /* Зелёный фон */
    color: #ffffff !important; /* Белый текст */
    border: none !important; /* Без границы */
}

/* Эффект при наведении на кнопку просмотра */
.cosmic-view:hover {
    background-color: #2dd4a5 !important; /* Светлее зелёный */
    transform: scale(1.1) !important; /* Увеличение на 10% */
    box-shadow: 0 3px 8px rgba(32, 201, 151, 0.3) !important; /* Зелёная тень */
}

/* Стили кнопки редактирования */
.cosmic-edit {
    background-color: #ffc107 !important; /* Жёлтый фон */
    color: #2a0a3a !important; /* Тёмный текст для контраста */
    border: none !important; /* Без границы */
}

/* Эффект при наведении на кнопку редактирования */
.cosmic-edit:hover {
    background-color: #ffca2c !important; /* Светлее жёлтый */
    transform: scale(1.1) !important; /* Увеличение на 10% */
    box-shadow: 0 3px 8px rgba(255, 193, 7, 0.3) !important; /* Жёлтая тень */
}

/* Стили кнопки удаления */
.cosmic-delete {
    background-color: #dc3545 !important; /* Красный фон */
    color: #ffffff !important; /* Белый текст */
    border: none !important; /* Без границы */
}

/* Эффект при наведении на кнопку удаления */
.cosmic-delete:hover {
    background-color: #e04b59 !important; /* Светлее красный */
    transform: scale(1.1) !important; /* Увеличение на 10% */
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.3) !important; /* Красная тень */
}

/* Стили уведомлений */
.cosmic-alert {
    padding: 1rem; /* Внутренние отступы */
    border-radius: 6px; /* Скруглённые углы */
    margin-bottom: 1.5rem; /* Отступ снизу */
}

/* Стили уведомления об ошибке */
.cosmic-alert-danger {
    background-color: rgba(255, 80, 80, 0.2); /* Полупрозрачный красный фон */
    border: 1px solid rgba(255, 80, 80, 0.4); /* Красная граница */
    color: #ffcccc; /* Светло-красный текст */
}

/* Стили текста ошибок валидации (для полноты) */
.cosmic-invalid-feedback {
    margin-top: 0.5rem; /* Отступ сверху */
    color: #ff6b6b; /* Красный текст */
    font-size: 0.875rem; /* Меньший размер текста */
}

/* Стили полей с ошибками */
.is-invalid {
    border-color: #ff6b6b !important; /* Красная граница */
}

/* Стили полей с ошибками при фокусе */
.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2) !important; /* Красная тень */
}

/* Стили текста */
.text-white {
    color: #ffffff !important; /* Белый цвет */
}

/* Стили текста с уменьшенной яркостью */
.text-white-50 {
    color: #d0d0d0 !important; /* Светло-серый цвет */
}

/* Стили таблицы (для полноты, здесь не используется) */
.cosmic-table {
    width: 100%; /* Ширина 100% */
    border-collapse: separate; /* Разделённые ячейки */
    border-spacing: 0; /* Без промежутков */
    background-color: rgba(40, 40, 50, 0.9); /* Тёмный фон */
}

/* Стили заголовков таблицы */
.cosmic-table th {
    background-color: rgba(90, 70, 120, 0.8); /* Фиолетовый фон */
    color: #ffffff; /* Белый текст */
    padding: 1rem; /* Внутренние отступы */
    text-align: left; /* Выравнивание слева */
    font-weight: 600; /* Полужирный шрифт */
}

/* Стили ячеек таблицы */
.cosmic-table td {
    padding: 1rem; /* Внутренние отступы */
    border-bottom: 1px solid rgba(90, 70, 120, 0.5); /* Фиолетовая граница снизу */
    vertical-align: middle; /* Центрирование по вертикали */
    color: #f0f0f0; /* Светлый текст */
}

/* Эффект при наведении на строку таблицы */
.cosmic-table-row:hover {
    background-color: rgba(90, 70, 120, 0.4); /* Полупрозрачный фиолетовый фон */
}

/* Стили пустого состояния таблицы */
.cosmic-empty-state {
    text-align: center; /* Центрирование текста */
    padding: 2rem; /* Внутренние отступы */
    color: #b0b0b0; /* Серый текст */
}

/* Стили имени в таблице */
.cosmic-name {
    font-weight: 500; /* Полужирный шрифт */
    color: #ffffff; /* Белый текст */
}

/* Стили даты в таблице */
.cosmic-date {
    color: #d0d0d0; /* Светло-серый текст */
}

/* Стили для элементов с эффектом при наведении */
.hover-effect {
    transition: all 0.3s ease; /* Плавная анимация изменений */
}

/* Эффект при наведении */
.hover-effect:hover {
    transform: translateY(-2px); /* Подъём на 2 пикселя вверх */
    box-shadow: 0 5px 15px rgba(106, 13, 173, 0.4); /* Фиолетовая тень */
}
</style>

<?php 
// Подключаем файл footer.php из папки templates — это нижняя часть сайта.
require_once __DIR__ . '/templates/footer.php';

// Завершаем буферизацию и отправляем всё из буфера в браузер.
ob_end_flush();
?>