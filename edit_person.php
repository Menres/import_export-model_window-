<?php
// Это открывающий тег PHP — начало кода на PHP.

// Включаем строгую типизацию — заставляем PHP строго проверять типы данных (числа, строки и т.д.).
declare(strict_types=1);

// Устанавливаем часовой пояс Москвы (UTC+3), чтобы даты и время были правильными.
date_default_timezone_set('Europe/Moscow');

// Запускаем сессию — это как коробка для хранения данных между страницами (например, фото).
session_start();

// Проверяем, работает ли сессия. Если нет, останавливаем скрипт с ошибкой.
if (session_status() !== PHP_SESSION_ACTIVE) {
    die("Ошибка: сессия не инициализирована!"); // die() — завершает работу скрипта.
}

// Включаем буферизацию вывода — всё, что выводим (HTML, текст), сначала собирается в памяти.
ob_start();

// Проверяем, не отправлены ли уже HTTP-заголовки (инструкции для браузера). Если да, выдаём ошибку.
if (headers_sent($file, $line)) {
    die("Ошибка: заголовки уже отправлены в файле $file на строке $line"); // $file и $line показывают место ошибки.
}

// Подключаем файл logic.php из той же папки — он содержит классы и функции для работы с базой и файлами.
require_once __DIR__ . '/logic.php'; // __DIR__ — путь к текущей папке.

// Подключаем нужные классы из logic.php, чтобы использовать их короче (без длинных имён).
use Repository\PersonRepository; // Для работы с таблицей людей.
use Repository\BrigadeRepository; // Для работы с таблицей бригад.
use Service\ImageUploader; // Для загрузки изображений.

// Создаём массив для ошибок — сюда будем записывать сообщения, если что-то введено неправильно.
$errors = [
    'full_name' => '', // Ошибка для поля "ФИО".
    'date_of_birth' => '', // Ошибка для поля "Дата рождения".
    'photo' => '', // Ошибка для поля "Фотография".
    'general' => '' // Общая ошибка для серьёзных проблем.
];

// Берем данные о загруженном фото из сессии, если оно есть (например, после Ctrl+V).
$uploadedPhoto = $_SESSION['uploaded_photo'] ?? null; // Если нет — null.

// Начинаем блок try-catch для обработки ошибок, чтобы скрипт не сломался.
try {
    // Проверяем, передан ли параметр 'id' в URL и является ли он числом.
    if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) { // $_GET — данные из URL.
        ob_end_clean(); // Очищаем буфер вывода.
        header("Location: index.php"); // Перенаправляем на главную страницу.
        exit; // Завершаем скрипт.
    }

    // Преобразуем 'id' из строки в число.
    $id = (int)$_GET['id'];

    // Подключаемся к базе данных через класс Database.
    $pdo = \Repository\Database::getConnection(); // $pdo — объект для работы с базой.

    // Создаём объекты для работы с таблицами людей и бригад.
    $personRepository = new PersonRepository($pdo); // Для людей.
    $brigadeRepository = new BrigadeRepository($pdo); // Для бригад.

    // Получаем данные сотрудника по ID из базы.
    $person = $personRepository->getPersonById($id);
    if (!$person) { // Если сотрудник не найден.
        ob_end_clean();
        header("Location: index.php"); // Перенаправляем на главную.
        exit;
    }

    // Проверяем, загружается ли фото через Ctrl+V (AJAX-запрос).
    if (isset($_POST['paste_image_upload']) && !empty($_FILES['pasted_image'])) { // $_FILES — данные о файле.
        try {
            // Указываем папку для загрузки фото.
            $uploadDir = __DIR__ . '/uploads/';

            // Если папки нет, создаём её с правами 0755 (чтение/запись для владельца).
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException('Не удалось создать директорию для загрузок'); // Ошибка, если не получилось.
            }
            // Проверяем, можно ли записывать в папку.
            if (!is_writable($uploadDir)) {
                throw new RuntimeException("Директория $uploadDir недоступна для записи");
            }

            // Создаём объект для загрузки фото.
            $imageUploader = new ImageUploader($uploadDir);

            // Если уже было фото в сессии, удаляем его с сервера.
            if ($uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
                @unlink(__DIR__ . '/' . $uploadedPhoto['path']); // @ — подавляет предупреждения.
            }

            // Загружаем новое фото и получаем путь.
            $photoPath = $imageUploader->upload($_FILES['pasted_image']);

            // Сохраняем данные о фото в сессии.
            $_SESSION['uploaded_photo'] = [
                'path' => $photoPath, // Путь к файлу.
                'name' => 'Вставленное изображение ' . date('Y-m-d H:i:s') // Имя с датой.
            ];

            // Очищаем буфер и отправляем ответ в формате JSON.
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'filename' => $_SESSION['uploaded_photo']['name'], 'path' => $photoPath]);
            exit; // Завершаем скрипт.
        } catch (RuntimeException $e) {
            // Если ошибка при загрузке, отправляем её в JSON.
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // Заполняем массив $formData текущими данными сотрудника для отображения в форме.
    $formData = [
        'full_name' => $person['full_name'] ?? '', // ФИО из базы.
        'brigade_id' => $person['brigade_id'] ?? null, // ID бригады или null.
        'date_of_birth' => isset($person['date_of_birth']) ? date('Y-m-d', strtotime($person['date_of_birth'])) : '', // Дата в формате для поля.
        'current_photo' => $person['photo'] ?? null // Текущее фото из базы.
    ];

    // Обрабатываем отправку формы (метод POST).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['paste_image_upload'])) { // $_SERVER — данные о запросе.
        // Обновляем $formData данными из формы.
        $formData['full_name'] = trim($_POST['full_name'] ?? ''); // Убираем лишние пробелы.
        $formData['brigade_id'] = !empty($_POST['brigade_id']) && $_POST['brigade_id'] != '0' ? (int)$_POST['brigade_id'] : null; // Бригада или null.
        $formData['date_of_birth'] = $_POST['date_of_birth'] ?? ''; // Дата рождения.

        // Валидация ФИО.
        $fullName = $formData['full_name'];
        if (empty($fullName)) {
            $errors['full_name'] = 'Поле ФИО обязательно для заполнения';
        } elseif (strlen($fullName) < 5 || strlen($fullName) > 100) {
            $errors['full_name'] = 'ФИО должно быть от 5 до 100 символов';
        } elseif (!preg_match('/^[а-яА-ЯёЁ\s-]+$/u', $fullName)) { // Проверяем русские буквы, пробелы, дефисы.
            $errors['full_name'] = 'ФИО может содержать только русские буквы, пробелы и дефисы';
        } elseif (count(explode(' ', trim($fullName))) < 2) { // Минимум два слова.
            $errors['full_name'] = 'ФИО должно содержать как минимум имя и фамилию';
        }

        // Валидация даты рождения.
        if (empty($formData['date_of_birth'])) {
            $errors['date_of_birth'] = 'Поле Дата рождения обязательно для заполнения';
        } else {
            try {
                $dateOfBirth = new DateTime($formData['date_of_birth']); // Преобразуем в объект даты.
                $today = new DateTime(); // Сегодня.
                $minAgeDate = (new DateTime())->sub(new DateInterval('P100Y')); // 100 лет назад.
                $maxAgeDate = (new DateTime())->sub(new DateInterval('P16Y')); // 16 лет назад.

                if ($dateOfBirth > $today) {
                    $errors['date_of_birth'] = 'Дата рождения не может быть в будущем';
                } elseif ($dateOfBirth > $maxAgeDate) {
                    $errors['date_of_birth'] = 'Возраст должен быть не менее 16 лет';
                } elseif ($dateOfBirth < $minAgeDate) {
                    $errors['date_of_birth'] = 'Дата рождения слишком далеко в прошлом (более 100 лет)';
                }
            } catch (Exception $e) {
                $errors['date_of_birth'] = 'Некорректный формат даты';
            }
        }

        // Устанавливаем путь к текущему фото.
        $photoPath = $formData['current_photo'];

        // Если загружается новое фото через форму.
        if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    throw new RuntimeException('Не удалось создать директорию для загрузок');
                }
                if (!is_writable($uploadDir)) {
                    throw new RuntimeException("Директория $uploadDir недоступна для записи");
                }

                $imageUploader = new ImageUploader($uploadDir);

                // Удаляем старое фото из базы, если оно было.
                if (!empty($formData['current_photo']) && file_exists($formData['current_photo'])) {
                    @unlink($formData['current_photo']);
                }
                // Удаляем временное фото из сессии, если оно было.
                if ($uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
                    @unlink(__DIR__ . '/' . $uploadedPhoto['path']);
                }

                // Загружаем новое фото.
                $photoPath = $imageUploader->upload($_FILES['photo']);
                $_SESSION['uploaded_photo'] = [
                    'path' => $photoPath,
                    'name' => $_FILES['photo']['name'] // Имя файла от пользователя.
                ];
                $uploadedPhoto = $_SESSION['uploaded_photo'];
            } catch (RuntimeException $e) {
                $errors['photo'] = $e->getMessage();
                if ($photoPath && file_exists(__DIR__ . '/' . $photoPath)) {
                    @unlink(__DIR__ . '/' . $photoPath);
                }
                unset($_SESSION['uploaded_photo']);
            }
        } elseif ($uploadedPhoto) { // Если фото уже в сессии (Ctrl+V).
            $photoPath = $uploadedPhoto['path'];
        }

        // Если ошибок нет, обновляем данные в базе.
        if (empty(array_filter($errors))) { // array_filter убирает пустые строки.
            if ($personRepository->updatePerson(
                $id, // ID сотрудника.
                $formData['full_name'],
                $formData['brigade_id'],
                $formData['date_of_birth'],
                $photoPath // Может быть null.
            )) {
                unset($_SESSION['uploaded_photo']); // Очищаем сессию.
                $_SESSION['person_updated'] = "Данные о пользователе обновлены (ID: $id)"; // Сообщение для главной страницы.
                error_log("Session person_updated set to: " . $_SESSION['person_updated']); // Логируем для отладки.
                ob_end_clean();
                header("Location: index.php"); // Перенаправляем.
                exit;
            }
            $errors['general'] = "Ошибка сохранения"; // Если обновление не удалось.
        }
    }

    // Получаем список всех бригад для выпадающего списка.
    $brigades = $brigadeRepository->getAllBrigades();

} catch (Exception $e) {
    // Ловим любые ошибки и записываем их в массив.
    $errors['general'] = $e->getMessage();
}

// Подключаем шапку страницы из файла header.php.
require_once __DIR__ . '/templates/header.php';
?>

<!-- Начинаем HTML-код страницы -->
<div class="container mt-4 animate__animated animate__fadeIn">
    <!-- Контейнер с отступами и анимацией -->
    <div class="row justify-content-center">
        <!-- Строка с центрированием -->
        <div class="col-lg-8">
            <!-- Колонка шириной 8 для больших экранов -->
            <div class="card cosmic-card">
                <!-- Карточка формы -->
                <div class="card-header cosmic-header">
                    <!-- Заголовок карточки -->
                    <h2 class="mb-0 text-white"><i class="bi bi-person-gear me-2"></i>Редактирование сотрудника</h2>
                    <!-- Заголовок с иконкой -->
                </div>
                
                <div class="card-body cosmic-body">
                    <!-- Тело карточки -->
                    <?php if ($errors['general']): ?>
                        <!-- Если есть общая ошибка, показываем её -->
                        <div class="alert cosmic-alert cosmic-alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errors['general']) ?>
                            <!-- Предупреждение с текстом ошибки -->
                        </div>
                    <?php endif; ?>

                    <!-- Форма редактирования -->
                    <form method="POST" enctype="multipart/form-data" novalidate class="cosmic-form" id="editPersonForm">
                        <!-- method="POST" — отправка данных; enctype — для файлов -->

                        <!-- Поле ФИО -->
                        <div class="cosmic-form-group">
                            <label for="full_name" class="cosmic-label">ФИО *</label>
                            <!-- Надпись "ФИО" с звёздочкой (обязательное) -->
                            <input type="text" 
                                   class="cosmic-input <?= $errors['full_name'] ? 'is-invalid' : '' ?>" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= htmlspecialchars($formData['full_name']) ?>" 
                                   required>
                            <!-- Поле ввода; если ошибка, красная рамка -->
                            <?php if ($errors['full_name']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['full_name']) ?>
                                    <!-- Ошибка под полем -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле бригады -->
                        <div class="cosmic-form-group">
                            <label for="brigade_id" class="cosmic-label">Бригада</label>
                            <select class="cosmic-select" id="brigade_id" name="brigade_id">
                                <!-- Выпадающий список -->
                                <option value="0">Не выбрана</option>
                                <!-- Первая опция — "не выбрано" -->
                                <?php foreach ($brigades as $brigade): ?>
                                    <!-- Цикл по бригадам -->
                                    <option value="<?= $brigade['id'] ?>"
                                        <?= $formData['brigade_id'] == $brigade['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brigade['name']) ?>
                                    </option>
                                    <!-- Опция с ID и именем; selected, если выбрана -->
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Поле даты рождения -->
                        <div class="cosmic-form-group">
                            <label for="date_of_birth" class="cosmic-label">Дата рождения *</label>
                            <input type="date" 
                                   class="cosmic-input <?= $errors['date_of_birth'] ? 'is-invalid' : '' ?>" 
                                   id="date_of_birth" 
                                   name="date_of_birth" 
                                   value="<?= htmlspecialchars($formData['date_of_birth']) ?>" 
                                   max="<?= date('Y-m-d') ?>" 
                                   min="<?= (new DateTime())->sub(new DateInterval('P100Y'))->format('Y-m-d') ?>"
                                   required>
                            <!-- Поле даты; max — сегодня, min — 100 лет назад -->
                            <?php if ($errors['date_of_birth']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['date_of_birth']) ?>
                                    <!-- Ошибка под полем -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле загрузки фото -->
                        <div class="cosmic-form-group">
                            <label for="photo" class="cosmic-label">Фотография</label>
                            <div class="cosmic-file-input" id="photo-drop-area" tabindex="0">
                                <!-- Область для drag-and-drop и Ctrl+V -->
                                <input type="file" 
                                       class="cosmic-file <?= $errors['photo'] ? 'is-invalid' : '' ?>" 
                                       id="photo" 
                                       name="photo" 
                                       accept="image/jpeg, image/png">
                                <!-- Поле выбора файла, только JPG/PNG -->
                                <input type="hidden" id="photo-input" name="photo-input">
                                <!-- Скрытое поле (не используется явно) -->
                                <label for="photo" class="cosmic-file-label">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>
                                    <span class="cosmic-file-text" id="file-name-display">
                                        <?php if ($uploadedPhoto): ?>
                                            <?= htmlspecialchars($uploadedPhoto['name']) ?>
                                            <!-- Имя загруженного фото из сессии -->
                                        <?php else: ?>
                                            Выберите файл или вставьте изображение (Ctrl+V)
                                            <!-- Подсказка -->
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </div>

                            <!-- Предпросмотр фото из сессии -->
                            <div class="cosmic-photo-preview" id="image-preview-container" style="display: <?= $uploadedPhoto ? 'block' : 'none' ?>;">
                                <img id="image-preview" src="<?= $uploadedPhoto ? htmlspecialchars($uploadedPhoto['path']) : '' ?>" alt="Предпросмотр фото" class="cosmic-preview-image">
                                <!-- Картинка предпросмотра -->
                                <button type="button" class="cosmic-remove-photo" id="removePhoto">
                                    <i class="bi bi-x-circle-fill"></i>
                                    <!-- Кнопка удаления -->
                                </button>
                            </div>

                            <div id="paste-error" class="cosmic-invalid-feedback" style="display: none;"></div>
                            <!-- Место для ошибок вставки -->
                            <div class="cosmic-form-text">
                                Разрешены только JPG и PNG изображения (макс. 5MB). Можно вставить из буфера обмена с помощью Ctrl+V.
                                <?php if ($uploadedPhoto): ?>
                                    <br><span style="color: #a0e8b0;">Файл загружен: <?= htmlspecialchars($uploadedPhoto['name']) ?></span>
                                    <a href="?id=<?= $id ?>&clear_photo=1" class="cosmic-btn cosmic-btn-outline" style="margin-left: 10px; padding: 0.25rem 0.5rem;">
                                        <i class="bi bi-x-lg"></i> Удалить
                                    </a>
                                    <!-- Если фото в сессии, показываем имя и кнопку удаления -->
                                <?php endif; ?>
                            </div>
                            <?php if ($errors['photo']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['photo']) ?>
                                    <!-- Ошибка загрузки -->
                                </div>
                            <?php endif; ?>

                            <!-- Показываем текущее фото из базы, если оно есть и нет нового в сессии -->
                            <?php if (!empty($formData['current_photo']) && !$uploadedPhoto): ?>
                                <div class="mt-3">
                                    <img src="<?= htmlspecialchars($formData['current_photo']) ?>" 
                                         class="cosmic-avatar border-purple" 
                                         style="max-width: 200px;">
                                    <!-- Текущее фото сотрудника -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Кнопки формы -->
                        <div class="cosmic-form-actions">
                            <a href="index.php" class="cosmic-btn cosmic-btn-outline">
                                <i class="bi bi-arrow-left me-2"></i> Назад
                                <!-- Ссылка на главную -->
                            </a>
                            <button type="submit" class="cosmic-btn cosmic-btn-primary">
                                <i class="bi bi-check-lg me-2"></i> Сохранить изменения
                                <!-- Кнопка отправки -->
                            </button>
                        </div>
                    </form>

                    <!-- Скрытая форма для загрузки через Ctrl+V -->
                    <form id="pasteImageForm" style="display: none;">
                        <input type="hidden" name="paste_image_upload" value="1">
                        <!-- Метка для сервера -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Если пользователь нажал "Удалить фото" (?clear_photo=1), удаляем фото из сессии.
if (isset($_GET['clear_photo']) && $uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
    unlink(__DIR__ . '/' . $uploadedPhoto['path']); // Удаляем файл с сервера.
    unset($_SESSION['uploaded_photo']); // Очищаем сессию.
    header("Location: edit_person.php?id=$id"); // Перезагружаем страницу с тем же ID.
    exit;
}
?>

<!-- Стили CSS для оформления -->
<style>
    /* Стиль карточки */
    .cosmic-card {
        background-color: rgba(35, 35, 45, 0.9); /* Тёмный фон с прозрачностью */
        border: 1px solid #4a3a6a; /* Фиолетовая рамка */
        border-radius: 10px; /* Закруглённые углы */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); /* Тень */
        backdrop-filter: blur(5px); /* Размытие фона */
        overflow: hidden; /* Убираем вылезание содержимого */
    }

    /* Заголовок карточки */
    .cosmic-header {
        background: linear-gradient(135deg, #6a0dad 0%, #4b0082 100%); /* Градиент */
        border-bottom: 1px solid #5a4a7a; /* Нижняя рамка */
        padding: 1.5rem; /* Отступы */
    }

    /* Тело карточки */
    .cosmic-body {
        padding: 2rem; /* Отступы */
        color: #e0e0e0; /* Светлый текст */
    }

    /* Группа элементов формы */
    .cosmic-form-group {
        margin-bottom: 1.5rem; /* Отступ снизу */
    }

    /* Надписи над полями */
    .cosmic-label {
        display: block; /* На новой строке */
        margin-bottom: 0.5rem; /* Отступ снизу */
        color: #d0d0d0; /* Светлый цвет */
        font-weight: 500; /* Полужирный текст */
    }

    /* Поля ввода и выпадающий список */
    .cosmic-input, .cosmic-select {
        display: block; /* На новой строке */
        width: 100%; /* Полная ширина */
        padding: 0.75rem 1rem; /* Отступы внутри */
        background-color: rgba(25, 25, 35, 0.8); /* Тёмный фон */
        border: 1px solid #4a3a6a; /* Фиолетовая рамка */
        border-radius: 6px; /* Закруглённые углы */
        color: #ffffff; /* Белый текст */
        font-size: 1rem; /* Размер шрифта */
        transition: all 0.3s ease; /* Плавные изменения */
    }

    /* Поля при фокусе */
    .cosmic-input:focus, .cosmic-select:focus {
        outline: none; /* Убираем стандартную обводку */
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
        box-shadow: 0 0 0 3px rgba(138, 109, 187, 0.2); /* Тень */
        background-color: rgba(30, 30, 40, 0.9); /* Светлее фон */
    }

    /* Область загрузки файла */
    .cosmic-file-input {
        position: relative; /* Для позиционирования */
        overflow: hidden; /* Убираем вылезание */
    }

    /* Поле файла (скрыто) */
    .cosmic-file {
        position: absolute; /* Прячем под меткой */
        left: 0;
        top: 0;
        opacity: 0; /* Невидимо */
        width: 100%;
        height: 100%;
        cursor: pointer; /* Курсор как рука */
    }

    /* Метка для поля файла */
    .cosmic-file-label {
        display: flex; /* Элементы в ряд */
        align-items: center; /* Центрируем по вертикали */
        padding: 0.75rem 1rem; /* Отступы */
        background-color: rgba(25, 25, 35, 0.8); /* Тёмный фон */
        border: 1px dashed #4a3a6a; /* Пунктирная рамка */
        border-radius: 6px; /* Закруглённые углы */
        color: #b0b0b0; /* Серый текст */
        transition: all 0.3s ease; /* Плавные изменения */
    }

    /* Метка при наведении */
    .cosmic-file-label:hover {
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
        background-color: rgba(30, 30, 40, 0.9); /* Светлее фон */
    }

    /* Метка, когда файл выбран */
    .cosmic-file-label.selected {
        border-style: solid; /* Сплошная рамка */
        border-color: #6a0dad; /* Фиолетовая рамка */
        background-color: rgba(106, 13, 173, 0.1); /* Прозрачный фиолетовый фон */
        color: #d0c0f0; /* Светлый текст */
    }

    /* Текст в метке */
    .cosmic-file-text {
        margin-left: 0.5rem; /* Отступ слева */
    }

    /* Подсказка под полем */
    .cosmic-form-text {
        margin-top: 0.5rem; /* Отступ сверху */
        font-size: 0.875rem; /* Меньший шрифт */
        color: #a0a0a0; /* Серый цвет */
    }

    /* Блок предпросмотра фото */
    .cosmic-photo-preview {
        margin-top: 1rem; /* Отступ сверху */
        position: relative; /* Для кнопки удаления */
        display: flex; /* Элементы в ряд */
        align-items: center; /* Центрируем по вертикали */
    }

    /* Картинка предпросмотра */
    .cosmic-preview-image {
        width: 100px; /* Ширина */
        height: 100px; /* Высота */
        object-fit: cover; /* Обрезаем, сохраняя пропорции */
        border-radius: 8px; /* Закруглённые углы */
        border: 2px solid #6a0dad; /* Фиолетовая рамка */
    }

    /* Кнопка удаления фото */
    .cosmic-remove-photo {
        position: absolute; /* Поверх картинки */
        top: -10px; /* Сдвиг вверх */
        right: -10px; /* Сдвиг вправо */
        background-color: #dc3545; /* Красный фон */
        border: none; /* Без рамки */
        border-radius: 50%; /* Круглая форма */
        width: 24px; /* Ширина */
        height: 24px; /* Высота */
        display: flex; /* Центрируем иконку */
        align-items: center;
        justify-content: center;
        color: white; /* Белая иконка */
        cursor: pointer; /* Курсор как рука */
        transition: all 0.3s ease; /* Плавные изменения */
    }

    /* Кнопка удаления при наведении */
    .cosmic-remove-photo:hover {
        background-color: #ff4d5e; /* Светлее красный */
        transform: scale(1.1); /* Увеличение */
    }

    /* Блок кнопок формы */
    .cosmic-form-actions {
        display: flex; /* Элементы в ряд */
        justify-content: space-between; /* Распределяем по краям */
        margin-top: 2rem; /* Отступ сверху */
        padding-top: 1.5rem; /* Отступ внутри сверху */
        border-top: 1px solid #3a3a4a; /* Линия сверху */
    }

    /* Общий стиль кнопок */
    .cosmic-btn {
        display: inline-flex; /* Элементы в ряд */
        align-items: center; /* Центрируем по вертикали */
        padding: 0.75rem 1.5rem; /* Отступы */
        border-radius: 6px; /* Закруглённые углы */
        font-weight: 500; /* Полужирный текст */
        transition: all 0.3s ease; /* Плавные изменения */
        border: none; /* Без рамки */
        cursor: pointer; /* Курсор как рука */
    }

    /* Кнопка "Назад" */
    .cosmic-btn-outline {
        background-color: transparent; /* Прозрачный фон */
        border: 1px solid #6a0dad; /* Фиолетовая рамка */
        color: #b0a0d0; /* Светло-фиолетовый текст */
    }

    /* "Назад" при наведении */
    .cosmic-btn-outline:hover {
        background-color: rgba(106, 13, 173, 0.1); /* Прозрачный фиолетовый фон */
        color: #d0c0f0; /* Светлее текст */
        transform: translateY(-2px); /* Поднимаем вверх */
    }

    /* Кнопка "Сохранить" */
    .cosmic-btn-primary {
        background-color: #6a0dad; /* Фиолетовый фон */
        color: white; /* Белый текст */
    }

    /* "Сохранить" при наведении */
    .cosmic-btn-primary:hover {
        background-color: #7b1de8; /* Светлее фиолетовый */
        transform: translateY(-2px); /* Поднимаем */
        box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3); /* Тень */
    }

    /* Стиль предупреждений */
    .cosmic-alert {
        padding: 1rem; /* Отступы */
        border-radius: 6px; /* Закруглённые углы */
        margin-bottom: 1.5rem; /* Отступ снизу */
    }

    /* Стиль ошибки */
    .cosmic-alert-danger {
        background-color: rgba(255, 80, 80, 0.15); /* Прозрачный красный фон */
        border: 1px solid rgba(255, 80, 80, 0.3); /* Красная рамка */
        color: #ff9e9e; /* Светло-красный текст */
    }

    /* Текст ошибок под полями */
    .cosmic-invalid-feedback {
        margin-top: 0.5rem; /* Отступ сверху */
        color: #ff6b6b; /* Красный цвет */
        font-size: 0.875rem; /* Меньший шрифт */
    }

    /* Активная ошибка */
    .cosmic-invalid-feedback.active {
        display: block; /* Показываем */
    }

    /* Поля с ошибкой */
    .is-invalid {
        border-color: #ff6b6b !important; /* Красная рамка */
    }

    /* Поле с ошибкой при фокусе */
    .is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2) !important; /* Красная тень */
    }

    /* Стиль аватара (текущее фото) */
    .cosmic-avatar {
        width: 200px; /* Ширина */
        height: 200px; /* Высота */
        border-radius: 50%; /* Круглая форма */
        object-fit: cover; /* Обрезаем, сохраняя пропорции */
        border: 2px solid #6a0dad; /* Фиолетовая рамка */
    }

    /* Область загрузки при наведении или перетаскивании */
    #photo-drop-area.drag-over, #photo-drop-area:hover {
        background-color: rgba(106, 13, 173, 0.1); /* Прозрачный фиолетовый фон */
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
    }

    /* Область при фокусе */
    #photo-drop-area:focus {
        outline: 2px solid blue; /* Синяя обводка */
    }
</style>

<!-- JavaScript для интерактивности -->
<script>
// Ждём, пока страница загрузится.
document.addEventListener('DOMContentLoaded', function() {
    // Находим элементы на странице.
    const dropArea = document.getElementById('photo-drop-area'); // Область загрузки.
    const fileInput = document.getElementById('photo'); // Поле выбора файла.
    const fileNameDisplay = document.getElementById('file-name-display'); // Текст с именем файла.
    const imagePreviewContainer = document.getElementById('image-preview-container'); // Блок предпросмотра.
    const imagePreview = document.getElementById('image-preview'); // Картинка предпросмотра.
    const pasteError = document.getElementById('paste-error'); // Сообщение об ошибке вставки.
    const pasteForm = document.getElementById('pasteImageForm'); // Скрытая форма для Ctrl+V.
    const mainForm = document.getElementById('editPersonForm'); // Основная форма.
    const photoInput = document.getElementById('photo-input'); // Скрытое поле.
    const removePhotoBtn = document.getElementById('removePhoto'); // Кнопка удаления фото.

    // Проверяем, все ли элементы найдены.
    if (!dropArea || !fileInput || !fileNameDisplay || !imagePreviewContainer || !imagePreview || !pasteError || !pasteForm || !mainForm || !photoInput || !removePhotoBtn) {
        console.error('One or more elements not found:', {
            dropArea, fileInput, fileNameDisplay, imagePreviewContainer, imagePreview, pasteError, pasteForm, mainForm, photoInput, removePhotoBtn
        });
        return; // Выходим, если что-то не найдено.
    }

    // Функция для предпросмотра картинки.
    function previewImage(file) {
        console.log('previewImage called with file:', file);
        // Проверяем тип файла (только JPG или PNG).
        if (!file || !file.type.match('image/jpeg') && !file.type.match('image/png')) {
            showError('Только JPG и PNG изображения разрешены');
            console.log('File type not allowed:', file ? file.type : 'No file');
            return false;
        }
        // Проверяем размер (не больше 5MB).
        if (file.size > 5 * 1024 * 1024) {
            showError('Размер файла не должен превышать 5MB');
            console.log('File size too large:', file.size);
            return false;
        }
        const reader = new FileReader(); // Объект для чтения файла.
        reader.onload = function(e) { // Когда файл прочитан.
            console.log('FileReader onload triggered');
            imagePreview.src = e.target.result; // Устанавливаем картинку.
            imagePreviewContainer.style.display = 'block'; // Показываем блок.
        };
        reader.onerror = function(e) { // Если ошибка.
            console.error('FileReader error:', e);
            showError('Ошибка чтения файла');
        };
        reader.readAsDataURL(file); // Читаем как URL.
        return true;
    }

    // Функция показа ошибок.
    function showError(message) {
        pasteError.textContent = message; // Устанавливаем текст.
        pasteError.style.display = 'block'; // Показываем.
        setTimeout(() => { // Скрываем через 5 секунд.
            pasteError.style.display = 'none';
        }, 5000);
    }

    // Функция обработки вставки через Ctrl+V.
    async function handlePaste() {
        try {
            // Проверяем разрешение на чтение буфера обмена.
            const permission = await navigator.permissions.query({ name: 'clipboard-read' });
            if (permission.state === 'denied') {
                console.log('Clipboard read permission denied');
                showError('Доступ к буферу обмена запрещен. Попробуйте выбрать файл вручную.');
                return;
            }

            // Читаем буфер обмена.
            const clipboardItems = await navigator.clipboard.read();
            let imageFound = false; // Флаг для картинки.
            for (const clipboardItem of clipboardItems) { // Перебираем элементы.
                for (const type of clipboardItem.types) {
                    if (type.startsWith('image/')) { // Если это изображение.
                        imageFound = true;
                        const blob = await clipboardItem.getType(type); // Получаем данные.
                        const file = new File([blob], 'pasted-image.png', { type: blob.type }); // Создаём файл.
                        if (previewImage(file)) { // Показываем предпросмотр.
                            const formData = new FormData(); // Объект для отправки.
                            formData.append('pasted_image', file); // Добавляем файл.
                            formData.append('paste_image_upload', '1'); // Метка для сервера.

                            // Отправляем на сервер.
                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json()) // Получаем JSON-ответ.
                            .then(data => {
                                if (data.success) { // Если успех.
                                    fileNameDisplay.textContent = data.filename || 'Вставленное изображение';
                                    imagePreview.src = data.path; // Обновляем предпросмотр.
                                    imagePreviewContainer.style.display = 'block';
                                    photoInput.value = ''; // Очищаем скрытое поле.
                                    pasteError.textContent = '';
                                    pasteError.style.display = 'none';
                                } else {
                                    showError('Ошибка загрузки: ' + data.error);
                                }
                            })
                            .catch(error => {
                                showError('Произошла ошибка при загрузке изображения: ' + error.message);
                            });
                        }
                        break; // Выходим из цикла.
                    }
                }
            }
            if (!imageFound) {
                console.log('No image found in clipboard');
                showError('Вставленный элемент не является изображением. Попробуйте выбрать файл вручную.');
            }
        } catch (error) {
            console.error('Clipboard API error:', error);
            showError('Не удалось получить доступ к буферу обмена. Попробуйте выбрать файл вручную.');
        }
    }

    // Ставим фокус на область загрузки при наведении мыши.
    dropArea.addEventListener('mouseover', function() {
        dropArea.focus();
    });

    // Ловим Ctrl+V на всей странице.
    document.addEventListener('keydown', async function(e) {
        if (e.ctrlKey && e.key === 'v') { // Если нажаты Ctrl и V.
            console.log('Document-level Ctrl+V detected');
            const activeElement = document.activeElement; // Какой элемент в фокусе.
            if (activeElement === dropArea || dropArea.contains(activeElement)) { // Если это область загрузки.
                e.preventDefault(); // Отменяем стандартное поведение.
                console.log('Handling Ctrl+V in drop area');
                handlePaste(); // Обрабатываем вставку.
            }
        }
    });

    // Поддержка drag-and-drop: файл над областью.
    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.classList.add('drag-over'); // Добавляем стиль.
    });

    // Файл убрали из области.
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('drag-over'); // Убираем стиль.
    });

    // Файл бросили в область.
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('drag-over');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) { // Если есть файл.
            fileInput.files = e.dataTransfer.files; // Устанавливаем в поле.
            fileNameDisplay.textContent = e.dataTransfer.files[0].name; // Показываем имя.
            if (previewImage(e.dataTransfer.files[0])) { // Показываем предпросмотр.
                // Не отправляем через fetch, так как это загрузка через форму.
            }
        }
    });

    // Обработчик выбора файла.
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) { // Если файл выбран.
            fileNameDisplay.textContent = this.files[0].name;
            if (previewImage(this.files[0])) {
                // Не отправляем через fetch, так как это загрузка через форму.
            }
        } else { // Если файл убрали.
            fileNameDisplay.textContent = 'Выберите файл или вставьте изображение (Ctrl+V)';
            imagePreviewContainer.style.display = 'none';
            imagePreview.src = '';
        }
    });

    // Обработчик удаления фото.
    removePhotoBtn.addEventListener('click', function() {
        console.log('Remove photo button clicked');
        fileInput.value = ''; // Очищаем поле.
        fileNameDisplay.textContent = 'Выберите файл или вставьте изображение (Ctrl+V)';
        imagePreviewContainer.style.display = 'none'; // Скрываем предпросмотр.
        imagePreview.src = '';
    });

    // Валидация формы на стороне клиента.
    mainForm.addEventListener('submit', function(event) {
        let fullNameInput = document.getElementById('full_name'); // Поле ФИО.
        let fullNameValue = fullNameInput.value.trim(); // Значение без пробелов.
        let fullNameError = document.querySelector('#full_name ~ .cosmic-invalid-feedback'); // Ошибка под полем.
        let dateInput = document.getElementById('date_of_birth'); // Поле даты.
        let dateValue = dateInput.value; // Значение даты.
        let dateError = document.querySelector('#date_of_birth ~ .cosmic-invalid-feedback'); // Ошибка под полем.
        let hasError = false; // Флаг ошибки.

        // Проверка ФИО.
        if (!fullNameValue) { // Если пусто.
            fullNameInput.classList.add('is-invalid'); // Красная рамка.
            if (!fullNameError) { // Если ошибки нет, создаём.
                fullNameError = document.createElement('div');
                fullNameError.className = 'cosmic-invalid-feedback';
                fullNameError.textContent = 'Поле ФИО обязательно для заполнения';
                fullNameInput.parentNode.appendChild(fullNameError);
            }
            hasError = true;
        } else if (fullNameValue.length < 5 || fullNameValue.length > 100) { // Проверка длины.
            fullNameInput.classList.add('is-invalid');
            if (!fullNameError) {
                fullNameError = document.createElement('div');
                fullNameError.className = 'cosmic-invalid-feedback';
                fullNameError.textContent = 'ФИО должно быть от 5 до 100 символов';
                fullNameInput.parentNode.appendChild(fullNameError);
            } else {
                fullNameError.textContent = 'ФИО должно быть от 5 до 100 символов';
            }
            hasError = true;
        } else if (!/^[а-яА-ЯёЁ\s-]+$/u.test(fullNameValue)) { // Проверка символов.
            fullNameInput.classList.add('is-invalid');
            if (!fullNameError) {
                fullNameError = document.createElement('div');
                fullNameError.className = 'cosmic-invalid-feedback';
                fullNameError.textContent = 'ФИО может содержать только русские буквы, пробелы и дефисы';
                fullNameInput.parentNode.appendChild(fullNameError);
            } else {
                fullNameError.textContent = 'ФИО может содержать только русские буквы, пробелы и дефисы';
            }
            hasError = true;
        } else if (fullNameValue.split(/\s+/).filter(word => word.length > 0).length < 2) { // Проверка на два слова.
            fullNameInput.classList.add('is-invalid');
            if (!fullNameError) {
                fullNameError = document.createElement('div');
                fullNameError.className = 'cosmic-invalid-feedback';
                fullNameError.textContent = 'ФИО должно содержать как минимум имя и фамилию';
                fullNameInput.parentNode.appendChild(fullNameError);
            } else {
                fullNameError.textContent = 'ФИО должно содержать как минимум имя и фамилию';
            }
            hasError = true;
        } else { // Если всё хорошо.
            fullNameInput.classList.remove('is-invalid');
            if (fullNameError) fullNameError.remove();
        }

        // Проверка даты.
        if (!dateValue) { // Если пусто.
            dateInput.classList.add('is-invalid');
            if (!dateError) {
                dateError = document.createElement('div');
                dateError.className = 'cosmic-invalid-feedback';
                dateError.textContent = 'Поле Дата рождения обязательно для заполнения';
                dateInput.parentNode.appendChild(dateError);
            }
            hasError = true;
        } else {
            let selectedDate = new Date(dateValue); // Преобразуем в дату.
            let today = new Date(); // Сегодня.
            let minDate = new Date(); // 100 лет назад.
            minDate.setFullYear(today.getFullYear() - 100);
            let maxAgeDate = new Date(); // 16 лет назад.
            maxAgeDate.setFullYear(today.getFullYear() - 16);

            if (selectedDate > today) { // Если дата в будущем.
                dateInput.classList.add('is-invalid');
                if (!dateError) {
                    dateError = document.createElement('div');
                    dateError.className = 'cosmic-invalid-feedback';
                    dateError.textContent = 'Дата рождения не может быть в будущем';
                    dateInput.parentNode.appendChild(dateError);
                } else {
                    dateError.textContent = 'Дата рождения не может быть в будущем';
                }
                hasError = true;
            } else if (selectedDate > maxAgeDate) { // Если младше 16.
                dateInput.classList.add('is-invalid');
                if (!dateError) {
                    dateError = document.createElement('div');
                    dateError.className = 'cosmic-invalid-feedback';
                    dateError.textContent = 'Возраст должен быть не менее 16 лет';
                    dateInput.parentNode.appendChild(dateError);
                } else {
                    dateError.textContent = 'Возраст должен быть не менее 16 лет';
                }
                hasError = true;
            } else if (selectedDate < minDate) { // Если старше 100.
                dateInput.classList.add('is-invalid');
                if (!dateError) {
                    dateError = document.createElement('div');
                    dateError.className = 'cosmic-invalid-feedback';
                    dateError.textContent = 'Дата рождения слишком далеко в прошлом (более 100 лет)';
                    dateInput.parentNode.appendChild(dateError);
                } else {
                    dateError.textContent = 'Дата рождения слишком далеко в прошлом (более 100 лет)';
                }
                hasError = true;
            } else { // Если всё хорошо.
                dateInput.classList.remove('is-invalid');
                if (dateError) dateError.remove();
            }
        }

        // Если есть ошибки, отменяем отправку.
        if (hasError) {
            event.preventDefault();
        }
    });
});
</script>

<?php 
// Подключаем подвал страницы из footer.php.
require_once __DIR__ . '/templates/footer.php';

// Отправляем всё из буфера в браузер.
ob_end_flush();
?>