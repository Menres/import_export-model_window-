<?php
// Это открывающий тег PHP — начало кода на PHP.


// Включаем строгую типизацию — заставляем PHP строго проверять типы данных (например, строка, число и т.д.).
declare(strict_types=1);

// Определяем пространство имён Repository — это как папка для классов, чтобы их имена не пересекались с другими.
namespace Repository;

// Класс Database отвечает за подключение к базе данных.
class Database {
    // Переменная для хранения подключения к базе. Она статическая (общая для всех объектов класса) и может быть null или PDO.
    private static ?\PDO $connection = null;

    // Метод для получения подключения к базе данных. Он статический, то есть вызывается без создания объекта (Database::getConnection()).
    public static function getConnection(): \PDO { // Возвращает объект PDO.
        // Проверяем, есть ли уже подключение.
        if (self::$connection === null) { // self::$connection — обращение к статической переменной.
            // Формируем строку подключения (DSN) к MySQL.
            $dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . // Хост базы (из переменной окружения или localhost).
                   ';dbname=' . (getenv('DB_NAME') ?: 'brigadalab') . // Имя базы (по умолчанию brigadalab).
                   ';charset=utf8mb4'; // Кодировка для поддержки русских букв и эмодзи.
            try { // Пробуем подключиться.
                // Создаём объект PDO для работы с базой.
                self::$connection = new \PDO(
                    $dsn, // Строка подключения.
                    getenv('DB_USER') ?: 'root', // Пользователь (по умолчанию root).
                    getenv('DB_PASS') ?: '', // Пароль (по умолчанию пустой).
                    [ // Настройки PDO.
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // Ошибки выбрасываются как исключения.
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // Данные возвращаются как ассоциативные массивы.
                        \PDO::ATTR_EMULATE_PREPARES => false // Используем настоящие подготовленные запросы для безопасности.
                    ]
                );
            } catch (\PDOException $e) { // Ловим ошибки подключения.
                // Записываем ошибку в лог с деталями (сообщение и стек вызовов).
                error_log("Database::getConnection: Ошибка подключения к базе данных: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
                // Выбрасываем исключение, чтобы сообщить об ошибке.
                throw new \RuntimeException('Не удалось подключиться к базе данных: ' . $e->getMessage());
            }
        }
        return self::$connection; // Возвращаем существующее или новое подключение.
    }

    // Метод для сброса подключения (например, если база временно недоступна).
    public static function resetConnection(): void { // void — метод ничего не возвращает.
        self::$connection = null; // Просто очищаем подключение, чтобы оно создалось заново при следующем вызове.
    }
}

// Класс PersonRepository для работы с таблицей people (люди).
class PersonRepository {
    // Переменная для хранения объекта PDO (подключения к базе). Она приватная — доступна только внутри класса.
    private \PDO $pdo;

    // Конструктор — вызывается при создании объекта (new PersonRepository()).
    public function __construct(\PDO $pdo) { // Принимает объект PDO.
        $this->pdo = $pdo; // Сохраняем подключение в переменную класса.
    }

    // Метод для получения информации о человеке по ID.
    public function getPersonById(int $id): ?array { // Принимает число (ID), возвращает массив или null.
        // Подготовленный запрос SQL: выбираем все данные о человеке и название бригады.
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name AS brigade_name 
            FROM people p 
            LEFT JOIN brigades b ON p.brigade_id = b.id 
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]); // Выполняем запрос, подставляя ID.
        return $stmt->fetch() ?: null; // Возвращаем данные как массив или null, если ничего не найдено.
    }

    // Метод для удаления всех записей из таблицы people.
    public function deleteAllPeople(): void {
        $stmt = $this->pdo->prepare("DELETE FROM people"); // Подготавливаем запрос на удаление.
        $stmt->execute(); // Выполняем его.
    }

    // Метод для получения всех людей из таблицы.
    public function getAllPeople(): array { // Возвращает массив.
        try {
            // SQL-запрос: выбираем все данные о людях и названия их бригад.
            $sql = "SELECT p.*, b.name AS brigade_name FROM people p LEFT JOIN brigades b ON p.brigade_id = b.id";
            $stmt = $this->pdo->prepare($sql); // Подготавливаем запрос.
            $stmt->execute(); // Выполняем.
            return $stmt->fetchAll(); // Возвращаем все строки как массив.
        } catch (\PDOException $e) { // Ловим ошибки базы.
            error_log("PersonRepository::getAllPeople: Ошибка при получении списка людей: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new \RuntimeException('Ошибка при получении списка людей: ' . $e->getMessage());
        }
    }

    // Метод для добавления нового человека.
    public function addPerson(
        string $fullName, // ФИО (обязательно строка).
        ?int $brigadeId, // ID бригады (может быть null).
        string $dateOfBirth, // Дата рождения (строка).
        ?string $photoPath = '' // Путь к фото (может быть null, по умолчанию пустая строка).
    ): ?int { // Возвращает ID нового человека или null.
        try {
            // Подготавливаем запрос на вставку данных в таблицу people.
            $stmt = $this->pdo->prepare("
                INSERT INTO people 
                (full_name, brigade_id, date_of_birth, photo) 
                VALUES (:full_name, :brigade_id, :date_of_birth, :photo)
            ");
            // Выполняем запрос с переданными данными.
            $stmt->execute([
                ':full_name' => $fullName,
                ':brigade_id' => $brigadeId, // PDO автоматически обработает null.
                ':date_of_birth' => $dateOfBirth,
                ':photo' => $photoPath
            ]);
            return (int)$this->pdo->lastInsertId(); // Возвращаем ID последней вставленной записи.
        } catch (\PDOException $e) {
            error_log("PersonRepository::addPerson: Ошибка при добавлении человека: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new \RuntimeException('Ошибка при добавлении человека: ' . $e->getMessage());
        }
    }

    // Метод для удаления человека по ID.
    public function deletePerson(int $id): bool { // Возвращает true, если удалено, иначе false.
        try {
            $person = $this->getPersonById($id); // Получаем данные о человеке.
            $stmt = $this->pdo->prepare("DELETE FROM people WHERE id = :id"); // Подготавливаем запрос.
            $stmt->execute([':id' => $id]); // Удаляем запись.
            // Если у человека было фото и файл существует, удаляем его.
            if ($person && !empty($person['photo']) && file_exists(__DIR__ . '/../' . $person['photo'])) {
                if (!unlink(__DIR__ . '/../' . $person['photo'])) { // Пробуем удалить файл.
                    error_log("PersonRepository::deletePerson: Не удалось удалить файл фото: " . $person['photo']);
                }
            }
            return $stmt->rowCount() > 0; // Проверяем, была ли удалена хотя бы одна строка.
        } catch (\PDOException $e) {
            error_log("PersonRepository::deletePerson: Ошибка при удалении человека: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            return false; // Если ошибка, возвращаем false.
        }
    }

    // Метод для обновления данных о человеке.
    public function updatePerson(
        int $id, // ID человека.
        string $fullName,
        ?int $brigadeId,
        string $dateOfBirth,
        ?string $photoPath = null // Путь к фото может быть null.
    ): bool { // Возвращает true при успехе, false при ошибке.
        try {
            // Базовый SQL-запрос для обновления.
            $sql = "UPDATE people SET full_name = :full_name, brigade_id = :brigade_id, date_of_birth = :date_of_birth";
            $params = [ // Параметры для запроса.
                ':id' => $id,
                ':full_name' => $fullName,
                ':brigade_id' => $brigadeId,
                ':date_of_birth' => $dateOfBirth
            ];
            // Если передали фото, добавляем его в запрос.
            if ($photoPath !== null) {
                $sql .= ", photo = :photo";
                $params[':photo'] = $photoPath;
            }
            $sql .= " WHERE id = :id"; // Условие — обновляем только нужную запись.
            $stmt = $this->pdo->prepare($sql); // Подготавливаем запрос.
            return $stmt->execute($params); // Выполняем и возвращаем результат (true/false).
        } catch (\PDOException $e) {
            error_log("PersonRepository::updatePerson: Ошибка при обновлении человека: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Метод для получения отфильтрованного списка людей.
    public function getFilteredPeople(array $filters): array { // Принимает массив фильтров, возвращает массив людей.
        try {
            // Базовый SQL-запрос с LEFT JOIN для получения названия бригады.
            $sql = "SELECT p.*, b.name AS brigade_name FROM people p LEFT JOIN brigades b ON p.brigade_id = b.id WHERE 1=1";
            $params = []; // Массив параметров для запроса.
            // Добавляем фильтры, если они есть.
            if (!empty($filters['full_name'])) { // Фильтр по ФИО.
                $sql .= " AND p.full_name LIKE :full_name"; // LIKE — поиск частичного совпадения.
                $params[':full_name'] = '%' . $filters['full_name'] . '%'; // % — любые символы до и после.
            }
            if (!empty($filters['year'])) { // Фильтр по году рождения.
                $sql .= " AND YEAR(p.date_of_birth) = :year"; // YEAR() — извлекает год из даты.
                $params[':year'] = (int)$filters['year'];
            }
            if (!empty($filters['brigade'])) { // Фильтр по бригаде.
                $sql .= " AND p.brigade_id = :brigade";
                $params[':brigade'] = (int)$filters['brigade'];
            }
            $stmt = $this->pdo->prepare($sql); // Подготавливаем запрос.
            $stmt->execute($params); // Выполняем с параметрами.
            return $stmt->fetchAll(); // Возвращаем все строки.
        } catch (\PDOException $e) {
            error_log("PersonRepository::getFilteredPeople: Ошибка при фильтрации людей: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            return []; // При ошибке возвращаем пустой массив.
        }
    }
}

// Класс BrigadeRepository для работы с таблицей brigades (бригады).
class BrigadeRepository {
    private \PDO $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo; // Сохраняем подключение.
    }

    // Метод для получения всех бригад.
    public function getAllBrigades(): array {
        try {
            $stmt = $this->pdo->query("SELECT * FROM brigades"); // Простой запрос без параметров.
            return $stmt->fetchAll(); // Возвращаем все бригады.
        } catch (\PDOException $e) {
            error_log("BrigadeRepository::getAllBrigades: Ошибка при получении списка бригад: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new \RuntimeException('Ошибка при получении списка бригад: ' . $e->getMessage());
        }
    }

    // Метод для удаления всех бригад.
    public function deleteAllBrigades(): void {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM brigades");
            $stmt->execute();
        } catch (\PDOException $e) {
            error_log("BrigadeRepository::deleteAllBrigades: Ошибка при удалении бригад: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new \RuntimeException('Ошибка при удалении бригад: ' . $e->getMessage());
        }
    }

    // Метод для добавления новой бригады.
    public function addBrigade(string $name): int { // Принимает имя, возвращает ID.
        try {
            $stmt = $this->pdo->prepare("INSERT INTO brigades (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            return (int)$this->pdo->lastInsertId(); // Возвращаем ID новой бригады.
        } catch (\PDOException $e) {
            error_log("BrigadeRepository::addBrigade: Ошибка при добавлении бригады: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw new \RuntimeException('Ошибка при добавлении бригады: ' . $e->getMessage());
        }
    }
}

// Новое пространство имён Service — для служебных классов.
namespace Service;

// Класс ImageUploader для загрузки изображений.
class ImageUploader {
    private string $uploadDir; // Папка, куда сохраняем файлы.
    // Массив допустимых типов файлов (MIME-типы и их расширения).
    private array $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    // Конструктор принимает путь к папке загрузки.
    public function __construct(string $uploadDir) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/'; // Убираем лишний слеш в конце и добавляем один.
        // Проверяем, существует ли папка.
        if (!is_dir($this->uploadDir)) {
            // Если нет, создаём её с правами 0755 (чтение и запись для владельца, чтение для остальных).
            if (!mkdir($this->uploadDir, 0755, true)) {
                error_log("ImageUploader::construct: Не удалось создать директорию для загрузки: " . $this->uploadDir);
                throw new \RuntimeException("Не удалось создать директорию для загрузки: " . $this->uploadDir);
            }
        }
    }

    // Метод для загрузки файла.
    public function upload(array $file): string { // Принимает массив $_FILES, возвращает путь к файлу.
        try {
            // Проверяем, нет ли ошибок при загрузке.
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('Ошибка загрузки файла: ' . $file['error']);
            }
            // Проверяем MIME-тип файла (например, image/jpeg).
            $finfo = new \finfo(FILEINFO_MIME_TYPE); // Объект для проверки типа.
            $mime = $finfo->file($file['tmp_name']); // Получаем MIME-тип временного файла.
            if (!in_array($mime, array_keys($this->allowedTypes))) { // Если тип не в списке разрешённых.
                throw new \RuntimeException('Разрешены только JPG и PNG изображения. Получен тип: ' . $mime);
            }
            $extension = $this->allowedTypes[$mime]; // Получаем расширение (jpg или png).
            $filename = uniqid() . '.' . $extension; // Создаём уникальное имя файла (например, 66f7a123.jpg).
            $destination = $this->uploadDir . $filename; // Полный путь для сохранения.
            // Перемещаем файл из временной папки в нужную.
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException('Не удалось сохранить файл в: ' . $destination);
            }
            return 'uploads/' . $filename; // Возвращаем относительный путь к файлу.
        } catch (\RuntimeException $e) {
            error_log("ImageUploader::upload: Ошибка при загрузке изображения: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            throw $e; // Перебрасываем исключение дальше.
        }
    }
}