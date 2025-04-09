<?php

// Включаем строгую типизацию — заставляем PHP проверять, что типы данных (числа, строки и т.д.) используются правильно.
declare(strict_types=1);

// Устанавливаем часовой пояс Москвы (UTC+3), чтобы все даты и время показывались по московскому времени.
date_default_timezone_set('Europe/Moscow');

// Начинаем буферизацию вывода — всё, что выводим (HTML, текст), сначала собирается в памяти, а не сразу отправляется в браузер.
ob_start();

// Устанавливаем HTTP-заголовки, чтобы браузер не сохранял страницу в кэше и всегда запрашивал свежую версию.
header('Cache-Control: no-cache, must-revalidate'); // Не кэшировать, всегда проверять.
header('Pragma: no-cache'); // Старый заголовок для совместимости.
header('Expires: 0'); // Срок действия страницы истёк.

// Запускаем сессию — это как коробка, где хранятся данные, доступные между страницами (например, сообщения об успехе).
session_start();

// Проверяем, работает ли сессия. Если нет, останавливаем скрипт с ошибкой.
if (session_status() !== PHP_SESSION_ACTIVE) {
    die("Ошибка: сессия не инициализирована!"); // die() — завершает выполнение скрипта и выводит сообщение.
}

// Подключаем файл logic.php из той же папки — он содержит классы для работы с базой данных и логикой приложения.
require_once __DIR__ . '/logic.php'; // __DIR__ — путь к текущей папке.

// Подключаем файл header.php из папки templates — это шапка сайта (HTML-код с меню, стилями и т.д.).
require_once __DIR__ . '/templates/header.php';

// Подключаем классы из logic.php, чтобы использовать их без длинных имён (например, PersonRepository вместо полного пути).
use Repository\PersonRepository; // Класс для работы с таблицей людей.
use Repository\BrigadeRepository; // Класс для работы с таблицей бригад.

// Генерируем CSRF-токен — это случайная строка для защиты формы удаления от подделки запросов (безопасность).
if (!isset($_SESSION['csrf_token'])) { // Если токена ещё нет в сессии.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Создаём 32-байтовую строку и преобразуем в читаемый вид.
}
$csrfToken = $_SESSION['csrf_token']; // Сохраняем токен в переменную для использования в форме.

// Извлекаем сообщения из сессии — они могли быть записаны на других страницах (например, после добавления сотрудника).
$importSuccess = $_SESSION['import_success'] ?? ''; // Сообщение об успешном импорте или пустая строка.
$importError = $_SESSION['import_error'] ?? ''; // Сообщение об ошибке импорта.
$importWarning = $_SESSION['import_warning'] ?? ''; // Предупреждение об импорте.
$personAdded = $_SESSION['person_added'] ?? ''; // Сообщение о добавлении сотрудника.
$personUpdated = $_SESSION['person_updated'] ?? ''; // Сообщение об обновлении сотрудника.
$personDeleted = $_SESSION['person_deleted'] ?? ''; // Сообщение об удалении сотрудника.

// Пишем в лог для отладки, чтобы проверить, что сообщения из сессии правильно извлечены.
error_log("Extracted person_added from session: " . $personAdded); // Записываем в лог-файл (обычно /var/log/php_errors.log).
error_log("Extracted person_updated from session: " . $personUpdated);

// Очищаем сообщения из сессии после их извлечения, чтобы они не показывались повторно при обновлении страницы.
unset(
    $_SESSION['import_success'], // Удаляем ключ import_success.
    $_SESSION['import_error'],
    $_SESSION['import_warning'],
    $_SESSION['person_added'],
    $_SESSION['person_updated'],
    $_SESSION['person_deleted']
);

// Создаём переменные для итоговых сообщений, которые покажем пользователю.
$successMessage = ''; // Сообщение об успехе.
$errorMessage = ''; // Сообщение об ошибке.

// Определяем, какое сообщение об успехе показать.
if ($importSuccess) { // Если есть сообщение об успешном импорте.
    $successMessage = $importSuccess;
} elseif ($personAdded || $personUpdated || $personDeleted) { // Если есть сообщение о добавлении, обновлении или удалении.
    $successMessage = $personAdded ?: $personUpdated ?: $personDeleted; // Берём первое непустое сообщение.
}

// Если есть сообщение об ошибке импорта, записываем его.
if ($importError) {
    $errorMessage = $importError;
}

// Начинаем блок try-catch для обработки ошибок, чтобы скрипт не сломался.
try {
    // Создаём объекты для работы с базой данных.
    $personRepository = new PersonRepository(\Repository\Database::getConnection()); // Для таблицы людей.
    $brigadeRepository = new BrigadeRepository(\Repository\Database::getConnection()); // Для таблицы бригад.

    // Создаём массив фильтров для поиска сотрудников.
    $filters = [];
    // Проверяем, есть ли параметры фильтрации в URL (например, index.php?full_name=Иванов&year=1990).
    if (isset($_GET['full_name']) || isset($_GET['year']) || isset($_GET['brigade'])) {
        $filters = [
            'full_name' => trim($_GET['full_name'] ?? ''), // ФИО без лишних пробелов или пустая строка.
            'year' => !empty($_GET['year']) ? (int)$_GET['year'] : null, // Год как число или null.
            'brigade' => !empty($_GET['brigade']) ? (int)$_GET['brigade'] : null // ID бригады или null.
        ];
    }

    // Обрабатываем удаление сотрудника (если форма отправлена с методом POST).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) { // $_SERVER — данные о запросе, $_POST — данные из формы.
        // Проверяем CSRF-токен для безопасности.
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $errorMessage = "Ошибка проверки CSRF-токена. Действие отклонено."; // Если токен не совпадает.
        } else {
            $id = (int)$_POST['delete_id']; // Преобразуем ID в число.
            if ($personRepository->deletePerson($id)) { // Пробуем удалить сотрудника.
                $_SESSION['person_deleted'] = "Запись успешно удалена"; // Записываем сообщение в сессию.
            } else {
                $errorMessage = "Не удалось удалить запись"; // Если удаление не удалось.
            }
        }
        ob_end_clean(); // Очищаем буфер вывода.
        header('Location: index.php'); // Перенаправляем на главную страницу.
        exit; // Завершаем скрипт.
    }

    // Получаем отфильтрованный список сотрудников и все бригады.
    $people = $personRepository->getFilteredPeople($filters); // Список людей с учётом фильтров.
    $brigades = $brigadeRepository->getAllBrigades(); // Все бригады из базы.

} catch (Throwable $e) { // Ловим любые ошибки (Throwable включает Exception и Error).
    error_log('Error in index.php: ' . $e->getMessage()); // Записываем ошибку в лог.
    $errorMessage = 'Произошла ошибка. Пожалуйста, попробуйте позже.'; // Сообщение для пользователя.
}
?>

<!-- Начинаем HTML-код страницы -->
<div class="container mt-4">
    <!-- Контейнер Bootstrap с отступом сверху -->
    <div class="card cosmic-card">
        <!-- Карточка для списка -->
        <div class="card-header cosmic-header">
            <!-- Заголовок карточки -->
            <h2 class="mb-0 text-white"><i class="bi bi-people-fill"></i> Список людей</h2>
            <!-- Иконка людей и текст -->
        </div>

        <div class="card-body cosmic-body">
            <!-- Тело карточки -->

            <!-- Показываем сообщение об успехе -->
            <?php if ($successMessage): ?>
                <div class="alert cosmic-alert cosmic-alert-success" role="alert">
                    <!-- Уведомление с зелёным фоном -->
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <!-- Иконка галочки -->
                    <?= htmlspecialchars($successMessage) ?>
                    <!-- Текст сообщения, защищённый от XSS -->
                    <button type="button" class="btn-close" aria-label="Закрыть"></button>
                    <!-- Кнопка закрытия уведомления -->
                </div>
            <?php endif; ?>

            <!-- Показываем сообщение об ошибке -->
            <?php if ($errorMessage): ?>
                <div class="alert cosmic-alert cosmic-alert-danger" role="alert">
                    <!-- Уведомление с красным фоном -->
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <!-- Иконка предупреждения -->
                    <?= htmlspecialchars($errorMessage) ?>
                    <!-- Текст ошибки -->
                    <button type="button" class="btn-close" aria-label="Закрыть"></button>
                </div>
            <?php endif; ?>

            <!-- Показываем предупреждение об импорте -->
            <?php if ($importWarning): ?>
                <div class="alert cosmic-alert cosmic-alert-warning" role="alert">
                    <!-- Уведомление с жёлтым фоном -->
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($importWarning) ?>
                    <button type="button" class="btn-close" aria-label="Закрыть"></button>
                </div>
            <?php endif; ?>

            <!-- Индикатор загрузки для импорта -->
            <div id="import-loading" style="display: none; text-align: center; margin-bottom: 1rem;">
                <i class="bi bi-arrow-repeat cosmic-spinner"></i> Загрузка...
                <!-- Иконка вращения и текст, скрыты по умолчанию -->
            </div>

            <!-- Форма фильтрации -->
            <form method="GET" class="cosmic-filter-form mb-4">
                <!-- Отправка методом GET для фильтров -->
                <div class="row g-3">
                    <!-- Строка с тремя колонками -->
                    <div class="col-md-4">
                        <!-- Колонка для ФИО -->
                        <label for="full_name" class="cosmic-label">ФИО</label>
                        <!-- Надпись над полем -->
                        <input type="text" 
                               class="cosmic-input" 
                               id="full_name" 
                               name="full_name" 
                               value="<?= htmlspecialchars($filters['full_name'] ?? '') ?>" 
                               placeholder="Введите ФИО">
                        <!-- Поле ввода ФИО с сохранённым значением -->
                    </div>
                    <div class="col-md-4">
                        <!-- Колонка для года -->
                        <label for="year" class="cosmic-label">Год рождения</label>
                        <input type="number" 
                               class="cosmic-input" 
                               id="year" 
                               name="year"
                               value="<?= !empty($filters['year']) ? htmlspecialchars((string)$filters['year']) : '' ?>"
                               placeholder="Введите год рождения"
                               min="1900" 
                               max="<?= date('Y') ?>">
                        <!-- Поле ввода года с ограничениями -->
                    </div>
                    <div class="col-md-4">
                        <!-- Колонка для бригады -->
                        <label for="brigade" class="cosmic-label">Бригада</label>
                        <select class="cosmic-select" id="brigade" name="brigade">
                            <!-- Выпадающий список -->
                            <option value="">Все бригады</option>
                            <!-- Опция "без фильтра" -->
                            <?php foreach ($brigades as $brigade): ?>
                                <!-- Цикл по бригадам -->
                                <option value="<?= $brigade['id'] ?>"
                                    <?= isset($filters['brigade']) && $filters['brigade'] == $brigade['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brigade['name']) ?>
                                </option>
                                <!-- Опция с ID и именем, selected если выбрана -->
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="cosmic-filter-actions">
                    <!-- Кнопки формы -->
                    <button type="submit" class="cosmic-btn cosmic-btn-primary">
                        <i class="bi bi-funnel-fill"></i> Фильтровать
                        <!-- Кнопка отправки формы -->
                    </button>
                    <a href="index.php" class="cosmic-btn cosmic-btn-outline">
                        <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                        <!-- Ссылка для сброса фильтров -->
                    </a>
                    <button type="button" class="cosmic-btn cosmic-btn-outline" id="openExportModal">
                        <i class="bi bi-download me-2"></i> Экспорт
                        <!-- Кнопка открытия модального окна -->
                    </button>
                    <label for="import-file" class="cosmic-btn cosmic-btn-outline" aria-label="Импорт данных">
                        <i class="bi bi-upload me-2"></i> Импорт
                        <!-- Метка для скрытого поля выбора файла -->
                    </label>
                    <input type="file" 
                           id="import-file" 
                           name="import_file" 
                           accept=".json,.csv,.xml" 
                           style="display: none;">
                    <!-- Скрытое поле для загрузки файла -->
                </div>
            </form>

            <!-- Модальное окно для выбора формата экспорта -->
            <div id="customExportModal" class="custom-modal" style="display: none;">
                <!-- Скрыто по умолчанию -->
                <div class="custom-modal-content">
                    <!-- Контент окна -->
                    <div class="custom-modal-header">
                        <h5 class="custom-modal-title">Выберите формат экспорта</h5>
                        <!-- Заголовок -->
                        <button type="button" class="custom-modal-close" id="closeExportModal">×</button>
                        <!-- Кнопка закрытия -->
                    </div>
                    <div class="custom-modal-body">
                        <p>Выберите формат, в котором хотите экспортировать данные:</p>
                        <div class="d-flex justify-content-center gap-3">
                            <!-- Кнопки форматов -->
                            <button type="button" class="custom-export-btn" data-format="json">JSON</button>
                            <button type="button" class="custom-export-btn" data-format="xml">XML</button>
                            <button type="button" class="custom-export-btn" data-format="csv">CSV</button>
                        </div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="custom-modal-cancel" id="cancelExportModal">Отмена</button>
                        <!-- Кнопка отмены -->
                    </div>
                </div>
            </div>

            <!-- Таблица сотрудников -->
            <div class="cosmic-table-container">
                <table class="cosmic-table">
                    <thead>
                        <!-- Заголовки таблицы -->
                        <tr>
                            <th style="width: 100px;">Фото</th>
                            <th>ФИО</th>
                            <th>Бригада</th>
                            <th>Дата рождения</th>
                            <th style="width: 180px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Тело таблицы -->
                        <?php if (empty($people)): ?>
                            <!-- Если сотрудников нет -->
                            <tr>
                                <td colspan="5" class="cosmic-empty-state">
                                    <i class="bi bi-emoji-frown"></i> Ничего не найдено
                                    <!-- Сообщение о пустом списке -->
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Если сотрудники есть -->
                            <?php foreach ($people as $person): ?>
                                <!-- Цикл по сотрудникам -->
                                <tr class="cosmic-table-row">
                                    <td>
                                        <!-- Фото -->
                                        <?php if (!empty($person['photo']) && file_exists($person['photo'])): ?>
                                            <!-- Если фото есть и файл существует -->
                                            <img src="<?= htmlspecialchars($person['photo']) ?>" 
                                                 alt="Фото <?= htmlspecialchars($person['full_name']) ?>" 
                                                 class="cosmic-avatar">
                                            <!-- Картинка с защитой от XSS -->
                                        <?php else: ?>
                                            <!-- Если фото нет -->
                                            <div class="cosmic-avatar-placeholder">
                                                <i class="bi bi-person-fill"></i>
                                                <!-- Плейсхолдер с иконкой -->
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cosmic-name"><?= htmlspecialchars($person['full_name']) ?></td>
                                    <!-- ФИО -->
                                    <td>
                                        <!-- Бригада -->
                                        <?php if (!empty($person['brigade_name'])): ?>
                                            <span class="cosmic-badge">
                                                <?= htmlspecialchars($person['brigade_name']) ?>
                                            </span>
                                            <!-- Название бригады -->
                                        <?php endif; ?>
                                    </td>
                                    <td class="cosmic-date">
                                        <!-- Дата рождения -->
                                        <?= !empty($person['date_of_birth']) ? date('d.m.Y', strtotime($person['date_of_birth'])) : 'Не указана' ?>
                                        <!-- Форматируем дату или показываем "Не указана" -->
                                    </td>
                                    <td>
                                        <!-- Действия -->
                                        <div class="cosmic-actions">
                                            <a href="watch_person.php?id=<?= $person['id'] ?>" 
                                               class="cosmic-action-btn cosmic-view"
                                               title="Подробнее">
                                                <i class="bi bi-search"></i>
                                                <!-- Кнопка просмотра -->
                                            </a>
                                            <a href="edit_person.php?id=<?= $person['id'] ?>" 
                                               class="cosmic-action-btn cosmic-edit"
                                               title="Изменить">
                                                <i class="bi bi-pencil-fill"></i>
                                                <!-- Кнопка редактирования -->
                                            </a>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Вы уверены, что хотите удалить <?= htmlspecialchars(addslashes($person['full_name'])) ?>?')">
                                                <!-- Форма удаления с подтверждением -->
                                                <input type="hidden" name="delete_id" value="<?= $person['id'] ?>">
                                                <!-- Скрытое поле с ID -->
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <!-- Скрытое поле с токеном -->
                                                <button type="submit" class="cosmic-action-btn cosmic-delete" title="Удалить">
                                                    <i class="bi bi-trash-fill"></i>
                                                    <!-- Кнопка удаления -->
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript для интерактивности -->
<script>
// Ждём, пока страница полностью загрузится.
document.addEventListener('DOMContentLoaded', function() {
    // Находим элементы для модального окна экспорта.
    const exportModal = document.getElementById('customExportModal'); // Модальное окно.
    const openExportModalBtn = document.getElementById('openExportModal'); // Кнопка открытия.
    const closeExportModalBtn = document.getElementById('closeExportModal'); // Крестик закрытия.
    const cancelExportModalBtn = document.getElementById('cancelExportModal'); // Кнопка "Отмена".
    const exportButtons = document.querySelectorAll('.custom-export-btn'); // Кнопки выбора формата.

    // Функция открытия модального окна.
    function openModal() {
        console.log('Открытие модального окна'); // Пишем в консоль для отладки.
        exportModal.style.display = 'flex'; // Показываем окно (flex для центрирования).
        document.body.style.overflow = 'hidden'; // Отключаем прокрутку страницы.
    }

    // Функция закрытия модального окна.
    function closeModal() {
        console.log('Закрытие модального окна');
        exportModal.style.display = 'none'; // Скрываем окно.
        document.body.style.overflow = 'auto'; // Включаем прокрутку обратно.
    }

    // Проверяем, есть ли кнопка открытия, и добавляем обработчик.
    if (openExportModalBtn) {
        openExportModalBtn.addEventListener('click', openModal); // При клике открываем окно.
    } else {
        console.error('Кнопка открытия модального окна не найдена'); // Ошибка в консоль, если кнопки нет.
    }

    // Закрытие по крестику.
    if (closeExportModalBtn) {
        closeExportModalBtn.addEventListener('click', closeModal);
    } else {
        console.error('Кнопка закрытия модального окна (крестик) не найдена');
    }

    // Закрытие по кнопке "Отмена".
    if (cancelExportModalBtn) {
        cancelExportModalBtn.addEventListener('click', closeModal);
    } else {
        console.error('Кнопка "Отмена" не найдена');
    }

    // Обработка кнопок экспорта (JSON, XML, CSV).
    if (exportButtons.length > 0) {
        exportButtons.forEach(button => { // Проходим по каждой кнопке.
            button.addEventListener('click', function() {
                console.log('Кнопка экспорта нажата:', this.getAttribute('data-format')); // Какой формат выбрали.
                const format = this.getAttribute('data-format'); // Получаем формат (json, xml, csv).
                closeModal(); // Закрываем окно.
                window.location.href = `import_export.php?action=export&format=${format}`; // Переходим на URL экспорта.
            });
        });
    } else {
        console.error('Кнопки экспорта не найдены');
    }

    // Закрытие модального окна при клике на фон.
    exportModal.addEventListener('click', function(event) {
        if (event.target === exportModal) { // Если кликнули на фон, а не на контент.
            closeModal();
        }
    });

    // Закрытие уведомлений (кнопки "X").
    const closeButtons = document.querySelectorAll('.btn-close'); // Все кнопки закрытия уведомлений.
    if (closeButtons.length > 0) {
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Кнопка закрытия уведомления нажата');
                const alert = this.closest('.alert'); // Находим ближайшее уведомление.
                if (alert) {
                    alert.style.display = 'none'; // Скрываем его.
                } else {
                    console.error('Родительский элемент .alert не найден');
                }
            });
        });
    } else {
        console.log('Кнопки закрытия уведомлений не найдены'); // Это нормально, если уведомлений нет.
    }

    // Обработка импорта файла.
    const importFileInput = document.getElementById('import-file'); // Поле выбора файла.
    const importLoading = document.getElementById('import-loading'); // Индикатор загрузки.

    // Проверяем, есть ли нужные элементы.
    if (importFileInput && importLoading) {
        importFileInput.addEventListener('change', async function() { // Когда выбрали файл.
            if (this.files.length === 0) return; // Если ничего не выбрали, выходим.

            const file = this.files[0]; // Берём первый файл.
            const fileExtension = file.name.split('.').pop().toLowerCase(); // Получаем расширение файла (json, csv, xml).
            const allowedExtensions = ['json', 'csv', 'xml']; // Допустимые форматы.

            // Проверяем формат файла.
            if (!allowedExtensions.includes(fileExtension)) {
                const alertsContainer = document.querySelector('.card-body.cosmic-body'); // Место для уведомлений.
                alertsContainer.insertAdjacentHTML('afterbegin',
                    '<div class="alert cosmic-alert cosmic-alert-danger">' +
                    '<i class="bi bi-exclamation-triangle-fill me-2"></i>' +
                    'Неподдерживаемый формат файла. Поддерживаются только JSON, CSV и XML.' +
                    '<button type="button" class="btn-close" aria-label="Закрыть"></button>' +
                    '</div>'
                ); // Добавляем ошибку в начало блока.
                importFileInput.value = ''; // Очищаем поле.
                return;
            }

            // Подтверждение импорта (он заменит все данные).
            if (!confirm('Импорт заменит все существующие данные. Продолжить?')) {
                importFileInput.value = ''; // Если отказались, очищаем поле.
                return;
            }

            // Показываем индикатор загрузки.
            importLoading.style.display = 'block';

            // Создаём объект FormData для отправки файла на сервер.
            const formData = new FormData();
            formData.append('import_file', file); // Добавляем файл.
            formData.append('action', 'import'); // Указываем действие.
            formData.append('format', fileExtension); // Указываем формат.

            try {
                // Отправляем файл на сервер через fetch (асинхронный запрос).
                const response = await fetch('import_export.php', {
                    method: 'POST', // Метод POST для отправки файла.
                    body: formData // Данные формы.
                });

                if (!response.ok) throw new Error(`Ошибка сервера: ${response.status}`); // Если сервер вернул ошибку.

                const data = await response.json(); // Получаем ответ в формате JSON.
                importLoading.style.display = 'none'; // Скрываем индикатор.
                const alertsContainer = document.querySelector('.card-body.cosmic-body');

                // Обрабатываем ответ.
                if (data.status === 'success') {
                    // Успешный импорт.
                    alertsContainer.insertAdjacentHTML('afterbegin',
                        '<div class="alert cosmic-alert cosmic-alert-success">' +
                        '<i class="bi bi-check-circle-fill me-2"></i>' + data.message +
                        '<button type="button" class="btn-close" aria-label="Закрыть"></button>' +
                        '</div>'
                    );
                    importFileInput.value = ''; // Очищаем поле.
                    setTimeout(() => window.location.href = 'index.php', 2000); // Через 2 секунды обновляем страницу.
                } else {
                    // Ошибка импорта.
                    alertsContainer.insertAdjacentHTML('afterbegin',
                        '<div class="alert cosmic-alert cosmic-alert-danger">' +
                        '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + data.message +
                        '<button type="button" class="btn-close" aria-label="Закрыть"></button>' +
                        '</div>'
                    );
                    importFileInput.value = '';
                }

                // Добавляем обработчики для кнопок закрытия новых уведомлений.
                const newCloseButtons = document.querySelectorAll('.btn-close');
                newCloseButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        console.log('Кнопка закрытия нового уведомления нажата');
                        const alert = this.closest('.alert');
                        if (alert) {
                            alert.style.display = 'none';
                        }
                    });
                });
            } catch (error) {
                // Ошибка при отправке или получении ответа.
                importLoading.style.display = 'none';
                const alertsContainer = document.querySelector('.card-body.cosmic-body');
                alertsContainer.insertAdjacentHTML('afterbegin',
                    '<div class="alert cosmic-alert cosmic-alert-danger">' +
                    '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + 'Ошибка при импорте: ' + error.message +
                    '<button type="button" class="btn-close" aria-label="Закрыть"></button>' +
                    '</div>'
                );
                importFileInput.value = '';

                // Обработчики для кнопок закрытия ошибочных уведомлений.
                const newCloseButtons = document.querySelectorAll('.btn-close');
                newCloseButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        console.log('Кнопка закрытия нового уведомления нажата');
                        const alert = this.closest('.alert');
                        if (alert) {
                            alert.style.display = 'none';
                        }
                    });
                });
            }
        });
    } else {
        console.error('Элементы #import-file или #import-loading не найдены в DOM'); // Ошибка, если элементы не найдены.
    }
});
</script>

<?php 
// Подключаем подвал страницы из footer.php (нижняя часть сайта).
require_once __DIR__ . '/templates/footer.php';

// Отправляем всё из буфера в браузер.
ob_end_flush();
?>