<?php
// Это открывающий тег PHP — начало кода на PHP.
// Включаем строгую типизацию — PHP будет строго проверять типы данных (например, числа, строки).
declare(strict_types=1);

// Устанавливаем часовой пояс Москвы (UTC+3), чтобы даты и время отображались правильно.
date_default_timezone_set('Europe/Moscow');

// Настраиваем отображение ошибок: не показываем их на экране, но записываем в лог-файл.
ini_set('display_errors', '0'); // Ошибки не показываем пользователям.
ini_set('log_errors', '1'); // Включаем запись ошибок в лог.
ini_set('error_log', '/var/log/php_errors.log'); // Указываем путь к файлу логов.

// Включаем буферизацию вывода — всё, что выводим, сначала собирается в памяти, а не сразу в браузер.
ob_start();

// Устанавливаем заголовки, чтобы браузер не кэшировал страницу (всегда загружал свежую версию).
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Запускаем сессию — это как коробка для хранения данных между страницами.
session_start();

// Проверяем, существует ли файл logic.php в текущей папке.
if (!file_exists(__DIR__ . '/logic.php')) { // __DIR__ — путь к текущей папке.
    error_log("import_export.php: Файл logic.php не найден в " . __DIR__); // Записываем ошибку в лог.
    ob_end_clean(); // Очищаем буфер вывода.
    header('Content-Type: application/json'); // Устанавливаем тип ответа — JSON.
    echo json_encode(['status' => 'error', 'message' => 'Внутренняя ошибка сервера: файл logic.php не найден']); // Отправляем ошибку в JSON.
    exit; // Завершаем скрипт.
}
require_once __DIR__ . '/logic.php'; // Подключаем logic.php с нужными классами.

// Подключаем классы из logic.php, чтобы использовать их короче.
use Repository\Database; // Для подключения к базе данных.
use Repository\PersonRepository; // Для работы с таблицей людей.
use Repository\BrigadeRepository; // Для работы с таблицей бригад.

// Пробуем подключиться к базе данных.
try {
    $connection = Database::getConnection(); // Получаем объект PDO для работы с базой.
    if (!$connection) { // Если подключение не удалось.
        throw new Exception('Соединение с базой данных не установлено'); // Выбрасываем ошибку.
    }
    error_log("import_export.php: Соединение с базой данных успешно установлено"); // Записываем успех в лог.
} catch (Exception $e) { // Ловим ошибки.
    error_log("import_export.php: Ошибка подключения к базе данных: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString()); // Логируем ошибку с деталями.
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Ошибка подключения к базе данных: ' . $e->getMessage()]);
    exit; // Завершаем скрипт.
}

// Определяем, что хочет пользователь: импорт или экспорт (берём из GET или POST).
$action = $_GET['action'] ?? $_POST['action'] ?? ''; // ?? — если нет значения, берём пустую строку.

// Логика импорта данных.
if ($action === 'import') {
    // Проверяем, что запрос — POST (импорт работает только с POST).
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // $_SERVER — данные о запросе.
        error_log("import_export.php: Неверный метод запроса для импорта: " . $_SERVER['REQUEST_METHOD']);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Неверный метод запроса. Ожидается POST']);
        exit;
    }

    // Начинаем обработку импорта.
    try {
        error_log("import_export.php: Начало обработки импорта");

        // Проверяем, загружен ли файл и нет ли ошибок.
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) { // $_FILES — данные о файле.
            throw new Exception('Файл не загружен или произошла ошибка при загрузке: ' . ($_FILES['import_file']['error'] ?? 'неизвестная ошибка'));
        }
        error_log("import_export.php: Файл успешно загружен: " . $_FILES['import_file']['tmp_name']);

        // Проверяем размер файла (максимум 5MB).
        if ($_FILES['import_file']['size'] > 5 * 1024 * 1024) { // 5 * 1024 * 1024 = 5 мегабайт в байтах.
            throw new Exception('Файл слишком большой. Максимальный размер: 5MB');
        }
        error_log("import_export.php: Размер файла корректный: " . $_FILES['import_file']['size'] . " байт");

        // Получаем формат файла из формы.
        $format = $_POST['format'] ?? '';
        $allowedFormats = ['json', 'csv', 'xml']; // Допустимые форматы.
        if (!in_array($format, $allowedFormats)) { // Проверяем, что формат поддерживается.
            throw new Exception('Неподдерживаемый формат файла: ' . htmlspecialchars($format)); // htmlspecialchars — защита от XSS.
        }
        error_log("import_export.php: Формат файла: " . $format);

        // Получаем путь к временному файлу.
        $filePath = $_FILES['import_file']['tmp_name'];
        $importData = []; // Массив для данных из файла.

        // Обрабатываем JSON.
        if ($format === 'json') {
            $jsonContent = file_get_contents($filePath); // Читаем файл как строку.
            $importDataRaw = json_decode($jsonContent, true); // Преобразуем JSON в массив.
            if (json_last_error() !== JSON_ERROR_NONE) { // Проверяем ошибки разбора JSON.
                throw new Exception('Ошибка при разборе JSON: ' . json_last_error_msg());
            }
            // Проверяем структуру JSON.
            if (!isset($importDataRaw['data'])) {
                if (!isset($importDataRaw['people'])) {
                    throw new Exception('Неверная структура JSON: отсутствует ключ "data" или "people"');
                }
                $importData = $importDataRaw; // Используем как есть, если есть "people".
            } else {
                $importData = ['people' => $importDataRaw['data']]; // Перекладываем "data" в "people".
            }
            error_log("import_export.php: JSON успешно декодирован: " . json_encode($importData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        // Обрабатываем CSV.
        elseif ($format === 'csv') {
            $importData = ['people' => []]; // Создаём пустой массив для людей.
            $fileHandle = fopen($filePath, 'r'); // Открываем файл для чтения.
            if ($fileHandle === false) {
                throw new Exception('Не удалось открыть CSV-файл для чтения');
            }

            // Определяем разделитель (запятая, точка с запятой или табуляция).
            $firstLine = fgets($fileHandle); // Читаем первую строку.
            rewind($fileHandle); // Возвращаемся в начало файла.
            $delimiter = ','; // По умолчанию — запятая.
            if (strpos($firstLine, ';') !== false) {
                $delimiter = ';'; // Если есть точка с запятой.
            } elseif (strpos($firstLine, "\t") !== false) {
                $delimiter = "\t"; // Если есть табуляция.
            }
            error_log("import_export.php: Определённый разделитель: '$delimiter'");

            // Читаем заголовки.
            $headers = fgetcsv($fileHandle, 0, $delimiter); // Получаем массив заголовков.
            if ($headers === false || empty($headers)) {
                fclose($fileHandle); // Закрываем файл.
                throw new Exception('CSV-файл пуст или не содержит заголовков');
            }

            // Приводим заголовки к нижнему регистру и убираем пробелы.
            $headers = array_map('trim', array_map('strtolower', $headers));
            error_log("import_export.php: Обработанные заголовки: " . json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Проверяем, что это данные о людях.
            $isPeople = in_array('full_name', $headers) || in_array('фио', $headers) || in_array('full name', $headers) || in_array('id', $headers);
            if (!$isPeople) {
                fclose($fileHandle);
                throw new Exception('CSV-файл не содержит данных о людях (ожидаются заголовки: full_name, фио, full name или id)');
            }

            // Читаем строки CSV.
            $rowCount = 0; // Счётчик строк.
            while (($row = fgetcsv($fileHandle, 0, $delimiter)) !== false) {
                $rowCount++;
                if (empty($row)) { // Пропускаем пустые строки.
                    error_log("import_export.php: Пропущена пустая строка #$rowCount");
                    continue;
                }
                $row = array_pad($row, count($headers), ''); // Дополняем пустыми значениями, если строк меньше заголовков.
                if (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers)); // Обрезаем лишние значения.
                }
                $person = array_combine($headers, $row); // Преобразуем строку в ассоциативный массив.
                if ($person === false) {
                    error_log("import_export.php: Ошибка в array_combine для строки #$rowCount");
                    continue;
                }
                // Определяем ID бригады.
                $brigadeId = null;
                if (isset($person['brigade_id']) && $person['brigade_id'] !== '') {
                    $brigadeId = (int)$person['brigade_id'];
                } elseif (isset($person['brigade id']) && $person['brigade id'] !== '') {
                    $brigadeId = (int)$person['brigade id'];
                }
                // Добавляем человека в массив.
                $importData['people'][] = [
                    'id' => isset($person['id']) && $person['id'] !== '' ? (int)$person['id'] : 0,
                    'full_name' => $person['full_name'] ?? $person['фио'] ?? $person['full name'] ?? (isset($person[1]) ? $person[1] : ''),
                    'brigade_id' => $brigadeId,
                    'date_of_birth' => !empty($person['date_of_birth']) ? $person['date_of_birth'] : '1970-01-01',
                    'photo' => !empty($person['photo']) ? $person['photo'] : null,
                    'created_at' => !empty($person['created_at']) ? $person['created_at'] : null
                ];
                error_log("import_export.php: Спарсена строка #$rowCount: " . json_encode($importData['people'][count($importData['people']) - 1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            fclose($fileHandle); // Закрываем файл.
            error_log("import_export.php: CSV успешно обработан: " . json_encode($importData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        // Обрабатываем XML.
        elseif ($format === 'xml') {
            $xmlContent = file_get_contents($filePath); // Читаем файл как строку.
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA); // Преобразуем в объект XML.
            if ($xml === false) {
                throw new Exception('Ошибка при разборе XML');
            }

            $importData = ['people' => []]; // Создаём массив для людей.
            // Проверяем структуру XML: <combined> или <people>.
            if (isset($xml->combined->person)) {
                foreach ($xml->combined->person as $person) {
                    $brigadeId = !empty($person->brigade_id) && (string)$person->brigade_id !== '' ? (int)$person->brigade_id : null;
                    $importData['people'][] = [
                        'id' => (string)$person->id !== '' ? (int)$person->id : 0,
                        'full_name' => (string)$person->full_name,
                        'brigade_id' => $brigadeId,
                        'date_of_birth' => !empty($person->date_of_birth) ? (string)$person->date_of_birth : '1970-01-01',
                        'photo' => !empty($person->photo) ? (string)$person->photo : null,
                        'created_at' => !empty($person->created_at) ? (string)$person->created_at : null
                    ];
                }
            } elseif (isset($xml->people->person)) {
                foreach ($xml->people->person as $person) {
                    $brigadeId = !empty($person->brigade_id) && (string)$person->brigade_id !== '' ? (int)$person->brigade_id : null;
                    $importData['people'][] = [
                        'id' => (string)$person->id !== '' ? (int)$person->id : 0,
                        'full_name' => (string)$person->full_name,
                        'brigade_id' => $brigadeId,
                        'date_of_birth' => !empty($person->date_of_birth) ? (string)$person->date_of_birth : '1970-01-01',
                        'photo' => !empty($person->photo) ? (string)$person->photo : null,
                        'created_at' => !empty($person->created_at) ? (string)$person->created_at : null
                    ];
                }
            }
            if (empty($importData['people'])) { // Если данных нет.
                throw new Exception('Неверная структура XML: отсутствуют данные о людях');
            }
            error_log("import_export.php: XML успешно обработан: " . json_encode($importData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Настраиваем PDO для работы с базой.
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Ошибки как исключения.
        $connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 0); // Отключаем автокоммит.

        // Начинаем транзакцию — все изменения в базе будут применены только в конце, если всё прошло успешно.
        $connection->beginTransaction();
        error_log("import_export.php: Транзакция начата");

        try {
            // Отключаем проверку внешних ключей для очистки таблицы.
            $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
            error_log("import_export.php: Проверка внешних ключей отключена");

            // Удаляем все данные из таблицы people.
            $connection->exec("DELETE FROM people");
            error_log("import_export.php: Таблица people очищена через DELETE");

            // Допустимые ID бригад (например, у нас только бригады 1, 2, 3).
            $allowedBrigadeIds = [1, 2, 3];

            // Если есть данные о людях, добавляем их в базу.
            if (!empty($importData['people'])) {
                // Подготовленный запрос для вставки или обновления записей.
                $stmtPeople = $connection->prepare("INSERT INTO people (id, full_name, brigade_id, date_of_birth, photo, created_at) VALUES (:id, :full_name, :brigade_id, :date_of_birth, :photo, :created_at) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), brigade_id = VALUES(brigade_id), date_of_birth = VALUES(date_of_birth), photo = VALUES(photo), created_at = VALUES(created_at)");

                foreach ($importData['people'] as $person) {
                    // Пропускаем людей без имени.
                    if (empty($person['full_name'])) {
                        error_log("import_export.php: Пропущен человек с пустым именем: " . json_encode($person));
                        continue;
                    }

                    // Проверяем brigade_id.
                    $brigadeId = $person['brigade_id'];
                    error_log("import_export.php: Исходный brigade_id: " . ($brigadeId === null ? 'NULL' : $brigadeId));
                    if ($brigadeId !== null && !in_array($brigadeId, $allowedBrigadeIds)) {
                        $brigadeId = null; // Если ID бригады недопустим, ставим null.
                        error_log("import_export.php: brigade_id вне диапазона [1, 2, 3], заменён на NULL: " . json_encode($person));
                    } else {
                        error_log("import_export.php: brigade_id сохранён: " . ($brigadeId === null ? 'NULL' : $brigadeId));
                    }

                    // Выполняем запрос с данными человека.
                    $stmtPeople->execute([
                        ':id' => (int)$person['id'],
                        ':full_name' => $person['full_name'],
                        ':brigade_id' => $brigadeId,
                        ':date_of_birth' => $person['date_of_birth'] ?? '1970-01-01',
                        ':photo' => $person['photo'] ?? null,
                        ':created_at' => $person['created_at'] ?? null
                    ]);
                    error_log("import_export.php: Добавлен человек: " . json_encode($person));
                }
            }

            // Включаем проверку внешних ключей обратно.
            $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
            error_log("import_export.php: Проверка внешних ключей включена");

            // Подтверждаем транзакцию — изменения сохраняются в базе.
            $connection->commit();
            error_log("import_export.php: Транзакция успешно завершена");

            // Включаем автокоммит обратно.
            $connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

            // Очищаем сессию от старых сообщений.
            $_SESSION = array_filter($_SESSION, function($key) {
                return !in_array($key, [
                    'import_success',
                    'import_error',
                    'import_warning',
                    'person_added',
                    'person_updated',
                    'person_deleted'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            // Формируем сообщение об успехе.
            $peopleCount = count($importData['people']);
            $message = "Данные успешно импортированы: $peopleCount человек.";
            $_SESSION['import_success'] = $message;

            // Отправляем ответ в JSON.
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => $message]);
            exit;
        } catch (PDOException $pdoEx) { // Ловим ошибки базы данных.
            if ($connection->inTransaction()) {
                $connection->rollBack(); // Откатываем изменения.
                error_log("import_export.php: Транзакция откатана из-за ошибки PDO: " . $pdoEx->getMessage());
            }
            $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
            $connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
            throw new Exception('Ошибка базы данных при импорте: ' . $pdoEx->getMessage());
        }
    } catch (Exception $e) { // Ловим остальные ошибки.
        if ($connection->inTransaction()) {
            $connection->rollBack();
            error_log("import_export.php: Транзакция откатана из-за общей ошибки: " . $e->getMessage());
        }
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        $connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        $errorMessage = 'Ошибка при импорте данных: ' . $e->getMessage();
        error_log("import_export.php: $errorMessage\nStack trace: " . $e->getTraceAsString());
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        exit;
    }
}

// Логика экспорта данных.
if ($action === 'export') {
    // Получаем формат экспорта (по умолчанию JSON).
    $format = $_GET['format'] ?? 'json';

    // Создаём объекты для работы с таблицами.
    $personRepository = new PersonRepository($connection);
    $brigadeRepository = new BrigadeRepository($connection);

    try {
        // Получаем всех людей и бригады из базы.
        $people = $personRepository->getAllPeople();
        $brigades = $brigadeRepository->getAllBrigades();

        error_log("import_export.php: Экспорт: Получено " . count($people) . " человек и " . count($brigades) . " бригад");

        // Создаём карту бригад (ID → имя) для удобства.
        $brigadesMap = [];
        foreach ($brigades as $brigade) {
            $brigadesMap[$brigade['id']] = $brigade['name'];
        }
        error_log("import_export.php: Карта бригад создана: " . json_encode($brigadesMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Экспорт в JSON.
        if ($format === 'json') {
            $combinedData = []; // Массив для объединённых данных.
            foreach ($people as $person) {
                $combinedData[] = [
                    'id' => $person['id'],
                    'full_name' => $person['full_name'],
                    'brigade_id' => $person['brigade_id'],
                    'brigade_name' => $person['brigade_id'] ? ($brigadesMap[$person['brigade_id']] ?? 'Unknown') : null,
                    'date_of_birth' => $person['date_of_birth'],
                    'photo' => $person['photo'],
                    'created_at' => $person['created_at']
                ];
            }

            // Формируем итоговый массив с датой экспорта.
            $data = [
                'data' => $combinedData,
                'exported_at' => date('Y-m-d H:i:s')
            ];

            // Отправляем файл JSON для скачивания.
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_combined.json"');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // Красивый JSON с русскими буквами.
            exit;
        }
        // Экспорт в CSV.
        elseif ($format === 'csv') {
            ob_end_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_combined.csv"');
            $output = fopen('php://output', 'w'); // Открываем поток для вывода в браузер.

            // Добавляем BOM для корректного отображения UTF-8 в Excel.
            fputs($output, "\xEF\xBB\xBF");

            // Записываем заголовки.
            fputcsv($output, ['id', 'full_name', 'brigade_id', 'brigade_name', 'date_of_birth', 'photo', 'created_at']);

            // Записываем данные о людях.
            foreach ($people as $person) {
                fputcsv($output, [
                    $person['id'],
                    $person['full_name'],
                    $person['brigade_id'] ?? '',
                    $person['brigade_id'] ? ($brigadesMap[$person['brigade_id']] ?? 'Unknown') : '',
                    $person['date_of_birth'] ?? '',
                    $person['photo'] ?? '',
                    $person['created_at'] ?? ''
                ]);
            }

            fclose($output); // Закрываем поток.
            exit;
        }
        // Экспорт в XML.
        elseif ($format === 'xml') {
            try {
                error_log("import_export.php: Начало формирования XML");

                // Создаём новый XML-документ.
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
                $xml->addChild('exported_at', htmlspecialchars(date('Y-m-d H:i:s'))); // Добавляем дату экспорта.

                // Добавляем данные о людях.
                $dataNode = $xml->addChild('combined');
                foreach ($people as $person) {
                    $personNode = $dataNode->addChild('person');
                    $personNode->addChild('id', htmlspecialchars((string)($person['id'] ?? '')));
                    $personNode->addChild('full_name', htmlspecialchars((string)($person['full_name'] ?? '')));
                    $personNode->addChild('brigade_id', htmlspecialchars((string)($person['brigade_id'] ?? '')));
                    $brigadeName = $person['brigade_id'] ? ($brigadesMap[$person['brigade_id']] ?? 'Unknown') : '';
                    $personNode->addChild('brigade_name', htmlspecialchars((string)$brigadeName));
                    $personNode->addChild('date_of_birth', htmlspecialchars((string)($person['date_of_birth'] ?? '')));
                    $personNode->addChild('photo', htmlspecialchars((string)($person['photo'] ?? '')));
                    $personNode->addChild('created_at', htmlspecialchars((string)($person['created_at'] ?? '')));
                }

                // Преобразуем XML в строку.
                $xmlString = $xml->asXML();
                if ($xmlString === false) {
                    throw new Exception('Не удалось сформировать XML');
                }

                error_log("import_export.php: XML успешно сформирован, длина: " . strlen($xmlString) . " байт");

                // Отправляем файл XML для скачивания.
                ob_end_clean();
                header('Content-Type: application/xml; charset=utf-8');
                header('Content-Disposition: attachment; filename="export_combined.xml"');
                echo $xmlString;
                exit;
            } catch (Exception $e) {
                error_log("import_export.php: Ошибка при формировании XML: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
                throw new Exception('Ошибка при формировании XML: ' . $e->getMessage());
            }
        }

        // Если формат неверный.
        throw new Exception('Неверный формат экспорта: ' . $format);
    } catch (Exception $e) {
        error_log("import_export.php: Ошибка при экспорте: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при экспорте: ' . $e->getMessage()]);
        exit;
    }
}

// Если действие не указано или неверное.
ob_end_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Неверное действие: ' . htmlspecialchars($action)]);
exit;
?>