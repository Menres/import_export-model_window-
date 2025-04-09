<?php
// Это открывающий тег PHP, он говорит серверу, что дальше будет код на языке PHP.
// Включаем строгую типизацию — это как строгие правила, чтобы PHP проверял типы данных (числа, строки и т.д.).
declare(strict_types=1);

// Устанавливаем часовой пояс Москвы, чтобы все даты и время в скрипте были по московскому времени (UTC+3).
date_default_timezone_set('Europe/Moscow');

// Запускаем сессию — это как коробка, где можно хранить данные между страницами (например, загруженное фото).
session_start();

// Проверяем, работает ли сессия. Если нет, останавливаем скрипт и показываем ошибку.
if (session_status() !== PHP_SESSION_ACTIVE) {
    die("Ошибка: сессия не инициализирована!"); // die() — это как "выключить" скрипт с сообщением.
}

// Включаем буферизацию вывода — всё, что скрипт хочет показать (HTML, текст), сначала собирается в памяти.
ob_start();

// Проверяем, не отправлены ли уже заголовки (инструкции для браузера). Если да, останавливаем скрипт с ошибкой.
if (headers_sent($file, $line)) {
    die("Ошибка: заголовки уже отправлены в файле $file на строке $line"); // $file и $line показывают, где проблема.
}

// Подключаем файл logic.php из той же папки, где лежит этот скрипт. Он содержит нужные классы и функции.
require_once __DIR__ . '/logic.php'; // __DIR__ — это путь к текущей папке.

// Подключаем классы из logic.php, чтобы использовать их без длинных имён (удобство).
use Repository\BrigadeRepository; // Для работы с таблицей бригад в базе данных.
use Repository\PersonRepository; // Для работы с таблицей людей в базе данных.
use Service\ImageUploader; // Для загрузки изображений.

// Создаём массив для ошибок, чтобы хранить сообщения, если что-то введено неправильно.
$errors = [
    'full_name' => '', // Ошибка для поля "ФИО".
    'date_of_birth' => '', // Ошибка для поля "Дата рождения".
    'photo' => '', // Ошибка для поля "Фотография".
    'general' => '' // Общая ошибка, если что-то сломалось.
];

// Собираем данные из формы, которые пользователь отправил через POST-запрос.
$formData = [
    'full_name' => $_POST['full_name'] ?? '', // Берем ФИО из формы, если его нет — пустая строка.
    'brigade_id' => !empty($_POST['brigade_id']) && $_POST['brigade_id'] != '0' ? (int)$_POST['brigade_id'] : null, // Бригада: если выбрана и не "0", делаем число, иначе null.
    'date_of_birth' => $_POST['date_of_birth'] ?? '' // Дата рождения, если нет — пустая строка.
];

// Берем данные о загруженном фото из сессии (если оно было загружено раньше, например, через Ctrl+V).
$uploadedPhoto = $_SESSION['uploaded_photo'] ?? null; // Если нет — null.

// Начинаем блок, где будем ловить ошибки, чтобы скрипт не сломался, если что-то пойдёт не так.
try {
    // Подключаемся к базе данных через класс Database из logic.php.
    $pdo = \Repository\Database::getConnection(); // $pdo — это как ключ для работы с базой.

    // Создаём объекты для работы с таблицами бригад и людей, передаём им подключение к базе.
    $brigadeRepository = new BrigadeRepository($pdo); // Для бригад.
    $personRepository = new PersonRepository($pdo); // Для людей.

    // Проверяем, загружает ли пользователь фото через Ctrl+V (AJAX-запрос).
    if (isset($_POST['paste_image_upload']) && !empty($_FILES['pasted_image'])) {
        try {
            // Указываем папку, куда будем сохранять фото.
            $uploadDir = __DIR__ . '/uploads/'; // Путь к папке uploads в текущей директории.

            // Если папки нет, создаём её с правами 0755 (чтение/запись для владельца).
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException('Не удалось создать директорию для загрузок'); // Ошибка, если не получилось.
            }

            // Проверяем, можно ли записывать файлы в эту папку.
            if (!is_writable($uploadDir)) {
                throw new RuntimeException("Директория $uploadDir недоступна для записи"); // Ошибка, если нельзя.
            }

            // Создаём объект для загрузки фото, передаём ему папку.
            $imageUploader = new ImageUploader($uploadDir);

            // Если уже было фото в сессии и оно существует, удаляем его, чтобы не захламлять сервер.
            if ($uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
                @unlink(__DIR__ . '/' . $uploadedPhoto['path']); // @ — чтобы не было предупреждений, если удаление не удалось.
            }

            // Загружаем новое фото и получаем путь к нему.
            $photoPath = $imageUploader->upload($_FILES['pasted_image']); // $_FILES — массив с данными о файле.

            // Сохраняем путь и имя файла в сессии, чтобы использовать позже.
            $_SESSION['uploaded_photo'] = [
                'path' => $photoPath, // Где лежит файл.
                'name' => 'Вставленное изображение ' . date('Y-m-d H:i:s') // Имя с датой для удобства.
            ];

            // Очищаем буфер вывода, чтобы отправить только нужный ответ.
            ob_end_clean();

            // Говорим браузеру, что отправляем данные в формате JSON.
            header('Content-Type: application/json');

            // Отправляем ответ в JSON: успех, имя файла и путь.
            echo json_encode(['success' => true, 'filename' => $_SESSION['uploaded_photo']['name'], 'path' => $photoPath]);
            exit; // Завершаем скрипт, чтобы ничего лишнего не выполнилось.
        } catch (RuntimeException $e) {
            // Если что-то пошло не так при загрузке, записываем ошибку в лог.
            error_log("add_person.php: Ошибка при загрузке вставленного изображения: " . $e->getMessage());

            // Очищаем буфер.
            ob_end_clean();

            // Устанавливаем тип ответа JSON.
            header('Content-Type: application/json');

            // Отправляем ошибку в JSON.
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit; // Завершаем скрипт.
        }
    }

    // Проверяем, отправлена ли основная форма (не загрузка фото через Ctrl+V).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['paste_image_upload'])) {
        // Валидация ФИО: убираем лишние пробелы в начале и конце.
        $fullName = trim($formData['full_name']);

        // Если ФИО пустое, записываем ошибку.
        if (empty($fullName)) {
            $errors['full_name'] = 'Поле ФИО обязательно для заполнения';
        } 
        // Проверяем длину ФИО: от 5 до 100 символов.
        elseif (strlen($fullName) < 5 || strlen($fullName) > 100) {
            $errors['full_name'] = 'ФИО должно быть от 5 до 100 символов';
        } 
        // Проверяем, что в ФИО только русские буквы, пробелы и дефисы.
        elseif (!preg_match('/^[а-яА-ЯёЁ\s-]+$/u', $fullName)) {
            $errors['full_name'] = 'ФИО может содержать только русские буквы, пробелы и дефисы';
        } 
        // Проверяем, что в ФИО минимум два слова (имя и фамилия).
        elseif (count(explode(' ', $fullName)) < 2) {
            $errors['full_name'] = 'ФИО должно содержать как минимум имя и фамилию';
        }

        // Валидация даты рождения.
        if (empty($formData['date_of_birth'])) {
            $errors['date_of_birth'] = 'Поле Дата рождения обязательно для заполнения';
        } else {
            try {
                // Преобразуем введённую дату в объект DateTime для проверки.
                $dob = new DateTime($formData['date_of_birth']);

                // Получаем текущую дату.
                $today = new DateTime();

                // Вычисляем дату 100 лет назад (минимальный возраст).
                $minAgeDate = (new DateTime())->sub(new DateInterval('P100Y'));

                // Вычисляем дату 16 лет назад (максимальный возраст).
                $maxAgeDate = (new DateTime())->sub(new DateInterval('P16Y'));

                // Если дата в будущем, ошибка.
                if ($dob > $today) {
                    $errors['date_of_birth'] = 'Дата рождения не может быть в будущем';
                } 
                // Если человек младше 16 лет, ошибка.
                elseif ($dob > $maxAgeDate) {
                    $errors['date_of_birth'] = 'Возраст должен быть не менее 16 лет';
                } 
                // Если человеку больше 100 лет, ошибка.
                elseif ($dob < $minAgeDate) {
                    $errors['date_of_birth'] = 'Дата рождения слишком далеко в прошлом (более 100 лет)';
                }
            } catch (Exception $e) {
                // Если дата введена неправильно (например, "123"), ошибка.
                $errors['date_of_birth'] = 'Некорректный формат даты рождения';
            }
        }

        // Устанавливаем путь к фото из сессии, если оно уже загружено.
        $photoPath = $uploadedPhoto ? $uploadedPhoto['path'] : null;

        // Проверяем, загружен ли новый файл через форму.
        if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                // Указываем папку для загрузки.
                $uploadDir = __DIR__ . '/uploads/';

                // Создаём папку, если её нет.
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    throw new RuntimeException('Не удалось создать директорию для загрузок');
                }

                // Проверяем, можно ли записывать в папку.
                if (!is_writable($uploadDir)) {
                    throw new RuntimeException("Директория $uploadDir недоступна для записи");
                }

                // Создаём объект для загрузки.
                $imageUploader = new ImageUploader($uploadDir);

                // Удаляем старое фото, если оно было.
                if ($uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
                    @unlink(__DIR__ . '/' . $uploadedPhoto['path']);
                }

                // Загружаем новое фото.
                $photoPath = $imageUploader->upload($_FILES['photo']);

                // Сохраняем данные о фото в сессии.
                $_SESSION['uploaded_photo'] = [
                    'path' => $photoPath,
                    'name' => $_FILES['photo']['name'] // Имя файла, как его назвал пользователь.
                ];
                $uploadedPhoto = $_SESSION['uploaded_photo'];
            } catch (RuntimeException $e) {
                // Если загрузка не удалась, записываем ошибку.
                $errors['photo'] = $e->getMessage();

                // Удаляем файл, если он был загружен, но что-то сломалось.
                if ($photoPath && file_exists(__DIR__ . '/' . $photoPath)) {
                    @unlink(__DIR__ . '/' . $photoPath);
                }
                unset($_SESSION['uploaded_photo']); // Очищаем сессию.
            }
        }

        // Если ошибок нет, сохраняем данные в базу.
        if (empty(array_filter($errors))) { // array_filter убирает пустые строки из $errors.
            try {
                // Добавляем человека в базу и получаем его ID.
                $personId = $personRepository->addPerson(
                    trim($formData['full_name']), // Убираем пробелы из ФИО.
                    $formData['brigade_id'], // ID бригады (может быть null).
                    $formData['date_of_birth'], // Дата рождения.
                    $photoPath // Путь к фото (может быть null).
                );

                // Удаляем данные о фото из сессии, так как они больше не нужны.
                unset($_SESSION['uploaded_photo']);

                // Записываем сообщение об успехе в сессию для показа на главной странице.
                $_SESSION['person_added'] = "Пользователь успешно добавлен (ID: $personId)";

                // Логируем успех для отладки.
                error_log("add_person.php: Session person_added set to: " . $_SESSION['person_added']);

                // Очищаем буфер и перенаправляем на главную страницу.
                ob_end_clean();
                header("Location: index.php");
                exit;
            } catch (Exception $e) {
                // Если сохранение не удалось, логируем ошибку.
                error_log("add_person.php: Ошибка при сохранении человека: " . $e->getMessage());

                // Удаляем фото, если оно было загружено, но не связано с сессией.
                if ($photoPath && file_exists(__DIR__ . '/' . $photoPath) && !$uploadedPhoto) {
                    @unlink(__DIR__ . '/' . $photoPath);
                }
                $errors['general'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
            }
        }
    }

    // Получаем список всех бригад из базы для выпадающего списка в форме.
    $brigades = $brigadeRepository->getAllBrigades();

} catch (Throwable $e) {
    // Ловим любые большие ошибки, которые не поймали раньше.
    error_log('Error in add_person.php: ' . $e->getMessage());
    $errors['general'] = 'Произошла ошибка: ' . $e->getMessage();
}

// Подключаем верхнюю часть страницы (шапку) из файла header.php.
require_once __DIR__ . '/templates/header.php';
?>

<!-- Начинаем HTML-код страницы -->
<div class="container mt-4 animate__animated animate__fadeIn">
    <!-- Контейнер для центрирования и отступов -->
    <div class="row justify-content-center">
        <!-- Строка с центрированием -->
        <div class="col-lg-8">
            <!-- Колонка шириной 8 для больших экранов -->
            <div class="card cosmic-card">
                <!-- Карточка с красивым стилем -->
                <div class="card-header cosmic-header">
                    <!-- Заголовок карточки -->
                    <h2 class="mb-0 text-white"><i class="bi bi-person-plus me-2"></i>Добавить нового человека</h2>
                    <!-- Заголовок "Добавить нового человека" с иконкой -->
                </div>
                
                <div class="card-body cosmic-body">
                    <!-- Тело карточки -->
                    <?php if ($errors['general']): ?>
                        <!-- Если есть общая ошибка, показываем её -->
                        <div class="alert cosmic-alert cosmic-alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errors['general']) ?>
                            <!-- Предупреждение с иконкой и текстом ошибки -->
                        </div>
                    <?php endif; ?>

                    <!-- Форма для ввода данных -->
                    <form method="POST" enctype="multipart/form-data" novalidate class="cosmic-form" id="personForm">
                        <!-- method="POST" — отправка данных на сервер; enctype — для файлов -->

                        <!-- Поле ФИО -->
                        <div class="cosmic-form-group">
                            <label for="full_name" class="cosmic-label">ФИО *</label>
                            <!-- Надпись "ФИО" с звёздочкой (обязательное поле) -->
                            <input type="text" 
                                   class="cosmic-input <?= $errors['full_name'] ? 'is-invalid' : '' ?>" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= htmlspecialchars($formData['full_name']) ?>" 
                                   required>
                            <!-- Поле ввода текста; если есть ошибка, добавляем красную рамку -->
                            <?php if ($errors['full_name']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['full_name']) ?>
                                    <!-- Показываем ошибку под полем -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле выбора бригады -->
                        <div class="cosmic-form-group">
                            <label for="brigade_id" class="cosmic-label">Бригада</label>
                            <select class="cosmic-select" id="brigade_id" name="brigade_id">
                                <!-- Выпадающий список -->
                                <option value="0">Не выбрана</option>
                                <!-- Первая опция — "не выбрано" -->
                                <?php foreach ($brigades as $brigade): ?>
                                    <!-- Цикл по всем бригадам из базы -->
                                    <option value="<?= $brigade['id'] ?>"
                                        <?= $formData['brigade_id'] == $brigade['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brigade['name']) ?>
                                    </option>
                                    <!-- Опция с ID и именем бригады; если выбрана, добавляем selected -->
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
                            <!-- Поле для даты; max — сегодня, min — 100 лет назад -->
                            <?php if ($errors['date_of_birth']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['date_of_birth']) ?>
                                    <!-- Показываем ошибку -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле для загрузки фото -->
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
                                            <!-- Если фото загружено, показываем его имя -->
                                        <?php else: ?>
                                            Выберите файл или вставьте изображение (Ctrl+V)
                                            <!-- Иначе подсказка -->
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </div>
                            
                            <!-- Предпросмотр фото -->
                            <div class="cosmic-photo-preview" id="image-preview-container" style="display: <?= $uploadedPhoto ? 'block' : 'none' ?>;">
                                <img id="image-preview" src="<?= $uploadedPhoto ? htmlspecialchars($uploadedPhoto['path']) : '' ?>" alt="Предпросмотр фото" class="cosmic-preview-image">
                                <!-- Картинка с предпросмотром -->
                                <button type="button" class="cosmic-remove-photo" id="removePhoto">
                                    <i class="bi bi-x-circle-fill"></i>
                                    <!-- Кнопка удаления фото -->
                                </button>
                            </div>
                            
                            <div id="paste-error" class="cosmic-invalid-feedback" style="display: none;"></div>
                            <!-- Место для ошибок при вставке фото -->
                            <div class="cosmic-form-text">
                                Разрешены только JPG и PNG изображения (макс. 5MB). Можно вставить из буфера обмена с помощью Ctrl+V.
                                <?php if ($uploadedPhoto): ?>
                                    <br><span style="color: #a0e8b0;">Файл загружен: <?= htmlspecialchars($uploadedPhoto['name']) ?></span>
                                    <a href="?clear_photo=1" class="cosmic-btn cosmic-btn-outline" style="margin-left: 10px; padding: 0.25rem 0.5rem;">
                                        <i class="bi bi-x-lg"></i> Удалить
                                    </a>
                                    <!-- Если фото есть, показываем его имя и кнопку удаления -->
                                <?php endif; ?>
                            </div>
                            <?php if ($errors['photo']): ?>
                                <div class="cosmic-invalid-feedback">
                                    <?= htmlspecialchars($errors['photo']) ?>
                                    <!-- Ошибка загрузки фото -->
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Кнопки формы -->
                        <div class="cosmic-form-actions">
                            <a href="index.php" class="cosmic-btn cosmic-btn-outline">
                                <i class="bi bi-arrow-left me-2"></i> Назад
                                <!-- Ссылка на главную страницу -->
                            </a>
                            <button type="submit" class="cosmic-btn cosmic-btn-primary">
                                <i class="bi bi-check-lg me-2"></i> Добавить
                                <!-- Кнопка отправки формы -->
                            </button>
                        </div>
                    </form>

                    <!-- Скрытая форма для загрузки фото через Ctrl+V -->
                    <form id="pasteImageForm" style="display: none;">
                        <input type="hidden" name="paste_image_upload" value="1">
                        <!-- Скрытое поле для обозначения AJAX-запроса -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Если пользователь нажал "Удалить фото" (?clear_photo=1), удаляем файл и очищаем сессию.
if (isset($_GET['clear_photo']) && $uploadedPhoto && file_exists(__DIR__ . '/' . $uploadedPhoto['path'])) {
    unlink(__DIR__ . '/' . $uploadedPhoto['path']); // Удаляем файл с сервера.
    unset($_SESSION['uploaded_photo']); // Удаляем данные из сессии.
    header("Location: add_person.php"); // Перезагружаем страницу.
    exit;
}
?>

<!-- Стили CSS для оформления страницы -->
<style>
    /* Стиль карточки формы */
    .cosmic-card {
        background-color: rgba(35, 35, 45, 0.9); /* Тёмный фон с прозрачностью */
        border: 1px solid #4a3a6a; /* Фиолетовая рамка */
        border-radius: 10px; /* Закруглённые углы */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); /* Тень */
        backdrop-filter: blur(5px); /* Размытие фона */
        overflow: hidden; /* Убираем вылезание содержимого */
    }

    /* Стиль заголовка карточки */
    .cosmic-header {
        background: linear-gradient(135deg, #6a0dad 0%, #4b0082 100%); /* Градиент от фиолетового к тёмному */
        border-bottom: 1px solid #5a4a7a; /* Нижняя рамка */
        padding: 1.5rem; /* Отступы внутри */
    }

    /* Стиль тела карточки */
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

    /* Стиль полей при фокусе (когда щёлкаешь на них) */
    .cosmic-input:focus, .cosmic-select:focus {
        outline: none; /* Убираем стандартную обводку */
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
        box-shadow: 0 0 0 3px rgba(138, 109, 187, 0.2); /* Тень вокруг */
        background-color: rgba(30, 30, 40, 0.9); /* Чуть светлее фон */
    }

    /* Область загрузки файла */
    .cosmic-file-input {
        position: relative; /* Для позиционирования дочерних элементов */
        overflow: hidden; /* Убираем вылезание содержимого */
    }

    /* Само поле файла (скрыто) */
    .cosmic-file {
        position: absolute; /* Прячем под меткой */
        left: 0;
        top: 0;
        opacity: 0; /* Невидимо */
        width: 100%; /* Полная ширина */
        height: 100%; /* Полная высота */
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

    /* Стиль метки при наведении */
    .cosmic-file-label:hover {
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
        background-color: rgba(30, 30, 40, 0.9); /* Светлее фон */
    }

    /* Стиль метки, когда файл выбран */
    .cosmic-file-label.selected {
        border-style: solid; /* Сплошная рамка */
        border-color: #6a0dad; /* Фиолетовая рамка */
        background-color: rgba(106, 13, 173, 0.1); /* Прозрачный фиолетовый фон */
        color: #d0c0f0; /* Светлый текст */
    }

    /* Текст внутри метки */
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
        object-fit: cover; /* Обрезаем картинку, сохраняя пропорции */
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

    /* Стиль кнопки "Назад" */
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

    /* Стиль кнопки "Добавить" */
    .cosmic-btn-primary {
        background-color: #6a0dad; /* Фиолетовый фон */
        color: white; /* Белый текст */
    }

    /* "Добавить" при наведении */
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

    /* Стиль текста ошибок под полями */
    .cosmic-invalid-feedback {
        margin-top: 0.5rem; /* Отступ сверху */
        color: #ff6b6b; /* Красный цвет */
        font-size: 0.875rem; /* Меньший шрифт */
    }

    /* Активная ошибка (показана) */
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

    /* Область загрузки фото */
    #photo-drop-area {
        border: 2px dashed #4a3a6a; /* Пунктирная фиолетовая рамка */
        border-radius: 8px; /* Закруглённые углы */
        transition: all 0.3s ease; /* Плавные изменения */
    }

    /* Область при наведении или перетаскивании */
    #photo-drop-area.drag-over, #photo-drop-area:hover {
        background-color: rgba(106, 13, 173, 0.1); /* Прозрачный фиолетовый фон */
        border-color: #8a6dbb; /* Светло-фиолетовая рамка */
    }

    /* Область при фокусе */
    #photo-drop-area:focus {
        outline: 2px solid blue; /* Синяя обводка */
    }

    /* Большое изображение (не используется тут) */
    .cosmic-image-preview {
        max-width: 100%; /* Максимальная ширина */
        max-height: 200px; /* Максимальная высота */
        border-radius: 6px; /* Закруглённые углы */
        border: 2px solid #4a3a6a; /* Фиолетовая рамка */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Тень */
    }

    /* Анимация загрузки (не используется тут) */
    .cosmic-loader {
        display: inline-block; /* В строке */
        width: 20px; /* Ширина */
        height: 20px; /* Выс requiringota */
        border: 3px solid rgba(138, 109, 187, 0.3); /* Прозрачная рамка */
        border-radius: 50%; /* Круг */
        border-top-color: #8a6dbb; /* Верхняя часть светлее */
        animation: spin 1s ease-in-out infinite; /* Вращение */
        margin-right: 8px; /* Отступ справа */
    }

    /* Определяем анимацию вращения */
    @keyframes spin {
        to { transform: rotate(360deg); } /* Полный поворот */
    }
</style>

<!-- JavaScript для интерактивности -->
<script>
// Ждём, пока страница полностью загрузится.
document.addEventListener('DOMContentLoaded', function() {
    // Находим элементы на странице по их ID.
    const dropArea = document.getElementById('photo-drop-area'); // Область загрузки фото.
    const fileInput = document.getElementById('photo'); // Поле выбора файла.
    const fileNameDisplay = document.getElementById('file-name-display'); // Текст с именем файла.
    const imagePreviewContainer = document.getElementById('image-preview-container'); // Блок предпросмотра.
    const imagePreview = document.getElementById('image-preview'); // Картинка предпросмотра.
    const pasteError = document.getElementById('paste-error'); // Сообщение об ошибке вставки.
    const pasteForm = document.getElementById('pasteImageForm'); // Скрытая форма для Ctrl+V.
    const mainForm = document.getElementById('personForm'); // Основная форма.
    const photoInput = document.getElementById('photo-input'); // Скрытое поле (не используется явно).
    const removePhotoBtn = document.getElementById('removePhoto'); // Кнопка удаления фото.

    // Проверяем, нашли ли все элементы. Если нет, пишем ошибку в консоль и выходим.
    if (!dropArea || !fileInput || !fileNameDisplay || !imagePreviewContainer || !imagePreview || !pasteError || !pasteForm || !mainForm || !photoInput || !removePhotoBtn) {
        console.error('One or more elements not found:', {
            dropArea, fileInput, fileNameDisplay, imagePreviewContainer, imagePreview, pasteError, pasteForm, mainForm, photoInput, removePhotoBtn
        });
        return; // Выходим из функции.
    }

    // Функция для показа предпросмотра картинки.
    function previewImage(file) {
        console.log('previewImage called with file:', file); // Пишем в консоль для отладки.
        // Проверяем, есть ли файл и подходит ли его тип (JPG или PNG).
        if (!file || (!file.type.match('image/jpeg') && !file.type.match('image/png'))) {
            showError('Только JPG и PNG изображения разрешены'); // Показываем ошибку.
            console.log('File type not allowed:', file ? file.type : 'No file');
            return false; // Возвращаем false, если ошибка.
        }
        // Проверяем размер файла (не больше 5MB).
        if (file.size > 5 * 1024 * 1024) {
            showError('Размер файла не должен превышать 5MB');
            console.log('File size too large:', file.size);
            return false;
        }
        // Создаём объект для чтения файла.
        const reader = new FileReader();
        // Когда файл прочитан, показываем его в предпросмотре.
        reader.onload = function(e) {
            console.log('FileReader onload triggered');
            imagePreview.src = e.target.result; // Устанавливаем картинку.
            imagePreviewContainer.style.display = 'block'; // Показываем блок.
        };
        // Если ошибка чтения, показываем сообщение.
        reader.onerror = function(e) {
            console.error('FileReader error:', e);
            showError('Ошибка чтения файла');
        };
        reader.readAsDataURL(file); // Читаем файл как URL-данные.
        return true; // Успех.
    }

    // Функция для показа ошибок.
    function showError(message) {
        pasteError.textContent = message; // Устанавливаем текст ошибки.
        pasteError.style.display = 'block'; // Показываем блок.
        // Скрываем ошибку через 5 секунд.
        setTimeout(() => {
            pasteError.style.display = 'none';
        }, 5000);
    }

    // Функция обработки вставки фото через Ctrl+V.
    async function handlePaste() {
        try {
            // Проверяем, есть ли разрешение на чтение буфера обмена.
            const permission = await navigator.permissions.query({ name: 'clipboard-read' });
            if (permission.state === 'denied') {
                console.log('Clipboard read permission denied');
                showError('Доступ к буферу обмена запрещен. Попробуйте выбрать файл вручную.');
                return;
            }

            // Читаем содержимое буфера обмена.
            const clipboardItems = await navigator.clipboard.read();
            let imageFound = false; // Флаг, нашли ли картинку.
            // Перебираем элементы в буфере.
            for (const clipboardItem of clipboardItems) {
                for (const type of clipboardItem.types) {
                    if (type.startsWith('image/')) { // Если это картинка.
                        imageFound = true;
                        const blob = await clipboardItem.getType(type); // Получаем данные.
                        const file = new File([blob], 'pasted-image.png', { type: blob.type }); // Создаём файл.
                        if (previewImage(file)) { // Показываем предпросмотр.
                            const formData = new FormData(); // Создаём объект для отправки данных.
                            formData.append('pasted_image', file); // Добавляем файл.
                            formData.append('paste_image_upload', '1'); // Метка для сервера.

                            // Отправляем файл на сервер.
                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json()) // Получаем ответ в JSON.
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
                                console.error('Fetch error:', error);
                                showError('Произошла ошибка при загрузке изображения: ' + error.message);
                            });
                        }
                        break; // Выходим из цикла, если нашли картинку.
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

    // При наведении мыши на область загрузки ставим фокус.
    dropArea.addEventListener('mouseover', function() {
        dropArea.focus();
    });

    // Ловим нажатие Ctrl+V на всей странице.
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

    // Поддержка drag-and-drop: когда файл над областью.
    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault(); // Отменяем стандартное поведение.
        dropArea.classList.add('drag-over'); // Добавляем стиль.
    });

    // Когда убираем файл из области.
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('drag-over'); // Убираем стиль.
    });

    // Когда бросаем файл в область.
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('drag-over');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) { // Если есть файлы.
            fileInput.files = e.dataTransfer.files; // Устанавливаем файлы в поле.
            fileNameDisplay.textContent = e.dataTransfer.files[0].name; // Показываем имя.
            if (previewImage(e.dataTransfer.files[0])) { // Показываем предпросмотр.
                // Не отправляем через fetch, так как это обычная загрузка через форму.
            }
        }
    });

    // Когда выбираем файл через поле.
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) { // Если файл выбран.
            fileNameDisplay.textContent = this.files[0].name;
            if (previewImage(this.files[0])) {
                // Не отправляем через fetch, так как это обычная загрузка через форму.
            }
        } else { // Если файл убрали.
            fileNameDisplay.textContent = 'Выберите файл или вставьте изображение (Ctrl+V)';
            imagePreviewContainer.style.display = 'none';
            imagePreview.src = '';
        }
    });

    // Обработчик кнопки удаления фото.
    removePhotoBtn.addEventListener('click', function() {
        console.log('Remove photo button clicked');
        fileInput.value = ''; // Очищаем поле файла.
        fileNameDisplay.textContent = 'Выберите файл или вставьте изображение (Ctrl+V)';
        imagePreviewContainer.style.display = 'none'; // Скрываем предпросмотр.
        imagePreview.src = '';
        // Отправляем запрос на сервер, чтобы удалить фото из сессии.
        fetch('?clear_photo=1', { method: 'GET' })
            .then(() => {
                window.location.reload(); // Перезагружаем страницу.
            })
            .catch(error => {
                console.error('Error clearing photo:', error);
            });
    });

    // Проверка формы перед отправкой (валидация на стороне клиента).
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
            if (!fullNameError) { // Если ошибки ещё нет, создаём.
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
            fullNameInput.classList.remove('is-invalid'); // Убираем красную рамку.
            if (fullNameError) fullNameError.remove(); // Убираем ошибку.
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
            let today = new Date(); // Сегодняшняя дата.
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

        // Если есть ошибки, отменяем отправку формы.
        if (hasError) {
            event.preventDefault();
        }
    });
});
</script>

<?php
// Подключаем нижнюю часть страницы (подвал) из файла footer.php.
require_once __DIR__ . '/templates/footer.php';

// Отправляем всё, что накопилось в буфере, браузеру.
ob_end_flush();
?>