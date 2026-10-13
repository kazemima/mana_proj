<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $this->pdo = $this->connect();
    }

    private function connect() {
        try {
            return $this->createConnection(DB_NAME);
        } catch (PDOException $e) {
            $code = (int)($e->errorInfo[1] ?? 0);

            // 1049 = Unknown database. Create it once, then connect again.
            if ($code === 1049) {
                $this->createDatabase();
                return $this->createConnection(DB_NAME);
            }

            throw new RuntimeException($this->describeError($e), 0, $e);
        }
    }

    private function createConnection($database = null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT;
        if ($database !== null) {
            $dsn .= ';dbname=' . $database;
        }
        $dsn .= ';charset=' . DB_CHARSET;

        if (!preg_match('/^[A-Za-z0-9_]+$/', DB_CHARSET) || !preg_match('/^[A-Za-z0-9_]+$/', DB_COLLATION)) {
            throw new RuntimeException('DB_CHARSET / DB_COLLATION معتبر نیستند.');
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('SET NAMES ' . DB_CHARSET . ' COLLATE ' . DB_COLLATION);
        return $pdo;
    }

    private function createDatabase() {
        if (!preg_match('/^[A-Za-z0-9_]+$/', DB_NAME)) {
            throw new RuntimeException('نام دیتابیس (DB_NAME) نامعتبر است: ' . DB_NAME);
        }
        try {
            $admin = $this->createConnection(null);
            $admin->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` DEFAULT CHARACTER SET '
                . DB_CHARSET . ' COLLATE ' . DB_COLLATION);
            $admin = null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'ساخت دیتابیس "' . DB_NAME . '" ممکن نشد (دسترسی CREATE لازم است) - ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function describeError(PDOException $e) {
        $code = (int)($e->errorInfo[1] ?? 0);
        $detail = $e->getMessage();

        if (stripos($detail, 'could not find driver') !== false) {
            return 'افزونه pdo_mysql فعال نیست. لطفاً در php.ini خطای extension=pdo_mysql را فعال کنید.';
        }

        switch ($code) {
            case 1045:
                return 'دسترسی به MySQL رد شد. نام کاربری یا رمز عبور دیتابیس (DB_USER / DB_PASS) نادرست است. ' . $detail;
            case 2002:
            case 2003:
                return 'اتصال به سرور MySQL برقرار نشد. میزبان یا پورت (DB_HOST / DB_PORT) را بررسی کنید. ' . $detail;
            case 1049:
                return 'دیتابیس "' . DB_NAME . '" پیدا نشد و ساخت آن ممکن نشد. ' . $detail;
            case 1044:
                return 'کاربر MySQL به دیتابیس "' . DB_NAME . '" دسترسی ندارد. ' . $detail;
            default:
                return 'خطای MySQL: ' . $detail;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function tableExists($table) {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?'
        );
        $stmt->execute([DB_NAME, $table]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Every statement is executed on its own: PDO MySQL does not accept a
     * multi-statement string the way PDO SQLite did.
     */
    private function schemaStatements() {
        $suffix = ' ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION;

        return [
            "CREATE TABLE IF NOT EXISTS settings (
                id INT NOT NULL AUTO_INCREMENT,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_settings_key (setting_key)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS users (
                id INT NOT NULL AUTO_INCREMENT,
                username VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(190) NOT NULL DEFAULT '',
                email VARCHAR(190) NULL DEFAULT '',
                role VARCHAR(50) NULL DEFAULT 'admin',
                last_login DATETIME NULL,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_users_username (username)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS sliders (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL DEFAULT '',
                subtitle VARCHAR(500) NULL DEFAULT '',
                description TEXT NULL,
                image VARCHAR(255) NULL DEFAULT '',
                link VARCHAR(500) NULL DEFAULT '',
                btn_text VARCHAR(190) NULL DEFAULT '',
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS services (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL,
                description TEXT NULL,
                short_description TEXT NULL,
                detail_content MEDIUMTEXT NULL,
                icon VARCHAR(100) NULL DEFAULT '',
                image VARCHAR(255) NULL DEFAULT '',
                slug VARCHAR(191) NULL DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                meta_title VARCHAR(300) NULL DEFAULT '',
                meta_description VARCHAR(600) NULL DEFAULT '',
                meta_keywords VARCHAR(500) NULL DEFAULT '',
                og_image VARCHAR(255) NULL DEFAULT '',
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_services_slug (slug)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS categories (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_categories_slug (slug)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS posts (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                content MEDIUMTEXT NULL,
                excerpt TEXT NULL,
                image VARCHAR(255) NULL DEFAULT '',
                category_id INT NOT NULL DEFAULT 0,
                author VARCHAR(190) NULL DEFAULT 'مدیریت',
                views INT NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                meta_title VARCHAR(300) NULL DEFAULT '',
                meta_description VARCHAR(600) NULL DEFAULT '',
                meta_keywords VARCHAR(500) NULL DEFAULT '',
                og_image VARCHAR(255) NULL DEFAULT '',
                canonical_url VARCHAR(500) NULL DEFAULT '',
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_posts_slug (slug)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS testimonials (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                role VARCHAR(190) NULL DEFAULT '',
                content TEXT NULL,
                image VARCHAR(255) NULL DEFAULT '',
                rating INT NOT NULL DEFAULT 5,
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS pages (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                content MEDIUMTEXT NULL,
                page_category VARCHAR(100) NULL DEFAULT 'general',
                page_template VARCHAR(100) NULL DEFAULT 'default',
                meta_title VARCHAR(300) NULL DEFAULT '',
                meta_description VARCHAR(600) NULL DEFAULT '',
                meta_keywords VARCHAR(500) NULL DEFAULT '',
                og_image VARCHAR(255) NULL DEFAULT '',
                canonical_url VARCHAR(500) NULL DEFAULT '',
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_pages_slug (slug)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS counter_items (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL,
                value INT NOT NULL DEFAULT 0,
                suffix VARCHAR(50) NULL DEFAULT '',
                icon VARCHAR(100) NULL DEFAULT '',
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                email VARCHAR(190) NULL DEFAULT '',
                phone VARCHAR(50) NULL DEFAULT '',
                subject VARCHAR(300) NULL DEFAULT '',
                message TEXT NULL,
                is_read TINYINT NOT NULL DEFAULT 0,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS faqs (
                id INT NOT NULL AUTO_INCREMENT,
                question VARCHAR(500) NOT NULL,
                answer TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS menu_items (
                id INT NOT NULL AUTO_INCREMENT,
                parent_id INT NOT NULL DEFAULT 0,
                title VARCHAR(190) NOT NULL,
                url VARCHAR(500) NULL DEFAULT '',
                target VARCHAR(20) NULL DEFAULT '_self',
                icon VARCHAR(100) NULL DEFAULT '',
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS menu_translations (
                id INT NOT NULL AUTO_INCREMENT,
                menu_id INT NOT NULL,
                lang_code VARCHAR(10) NOT NULL,
                title VARCHAR(300) NULL DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY uniq_menu_translations (menu_id, lang_code)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS languages (
                id INT NOT NULL AUTO_INCREMENT,
                code VARCHAR(10) NOT NULL,
                name VARCHAR(100) NOT NULL,
                native_name VARCHAR(100) NOT NULL DEFAULT '',
                direction VARCHAR(10) NULL DEFAULT 'ltr',
                flag VARCHAR(50) NULL DEFAULT '',
                sort_order INT NOT NULL DEFAULT 0,
                is_default TINYINT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_languages_code (code)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS translations (
                id INT NOT NULL AUTO_INCREMENT,
                lang_code VARCHAR(10) NOT NULL,
                trans_key VARCHAR(150) NOT NULL,
                trans_value TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_translations (lang_code, trans_key)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS post_translations (
                id INT NOT NULL AUTO_INCREMENT,
                post_id INT NOT NULL,
                lang_code VARCHAR(10) NOT NULL,
                title VARCHAR(500) NULL DEFAULT '',
                excerpt TEXT NULL,
                content MEDIUMTEXT NULL,
                meta_title VARCHAR(300) NULL DEFAULT '',
                meta_description TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_post_translations (post_id, lang_code)
            )" . $suffix,

            "CREATE TABLE IF NOT EXISTS service_translations (
                id INT NOT NULL AUTO_INCREMENT,
                service_id INT NOT NULL,
                lang_code VARCHAR(10) NOT NULL,
                title VARCHAR(500) NULL DEFAULT '',
                description TEXT NULL,
                short_description TEXT NULL,
                detail_content MEDIUMTEXT NULL,
                meta_title VARCHAR(300) NULL DEFAULT '',
                meta_description TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_service_translations (service_id, lang_code)
            )" . $suffix,

            // Used by admin/forgot-password.php and admin/reset-password.php.
            "CREATE TABLE IF NOT EXISTS password_resets (
                id INT NOT NULL AUTO_INCREMENT,
                email VARCHAR(190) NOT NULL,
                token VARCHAR(64) NOT NULL,
                created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_password_resets_token (token),
                KEY idx_password_resets_email (email)
            )" . $suffix,
        ];
    }

    /** Creates all tables (idempotent) without inserting any seed data. */
    public function initSchema() {
        foreach ($this->schemaStatements() as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function initDatabase() {
        $this->initSchema();

        $this->seedData();
    }

    private function getServiceFeatures($title) {
        $features = [
            'عمومی' => [
                ['امکان تعریف کاربران', 'تعریف کاربران با مشخصات شخصی و سازمانی مورد نیاز تکمیل و همچنین تصویر شخص و امضای الکترونیک'],
                ['کاربران متصل', 'مشخصات کامل پرسنل متصل شده به سامانه مانند زمان ورود، پردازش و...'],
                ['جدول مشاغل', 'ایجاد، ویرایش و مشاهده عناوین شغلی در سیستم'],
                ['بخش های شغلی', 'مشخصات حوزه های اطلاعاتی اعم از عنوان حوزه مرجع، کد حوزه و...'],
                ['دسترسی درختی', 'کلیه فرم های نرم افزار به همراه دسترسی های درج، حذف، ویرایش و مشاهده'],
                ['تقویم کاری', 'این تقویم به صورت کاملا بصری کلیه ارجاعات پرسنل را مدیریت میکند'],
                ['میز کار', 'امکان نمایش شاخص ها و برنامه های عملیاتی منتخب کاربر'],
            ],
            'برنامه عملیاتی' => [
                ['تعریف برنامه های عملیاتی', 'تعریف انواع برنامه های عملیاتی شرکت'],
                ['گزارش گیری', 'گزارش گیری از برنامه های عملیاتی'],
                ['ربات های ناظر', 'استفاده از ربات های ناظر برای پیگیری برنامه ها'],
                ['پیگیری عملکرد', 'پیگیری عملکرد پرسنل در راستای برنامه های تعریف شده'],
            ],
            'شاخص' => [
                ['تعریف شاخص ها', 'امکان تعریف انواع شاخص های کلیدی عملکرد'],
                ['گزارش های متنوع', 'گزارش های متنوع و کاربردی در مورد انواع شاخص ها'],
                ['نمودار عملکرد', 'نمایش نموداری عملکرد در بازه های زمانی مختلف'],
            ],
            'جلسات' => [
                ['تعریف کمیته ها', 'تعریف کمیته ها و اعضاء'],
                ['تنظیم جلسات', 'نظامی قوی در تنظیم جلسات'],
                ['ثبت صورت جلسات', 'ثبت صورت جلسات و نتایج'],
                ['نظر سنجی', 'انجام نظر سنجی از اعضاء'],
                ['گزارشات متنوع', 'گزارشات متنوع و کاربردی از جلسات برگزار شده'],
            ],
            'مستندات' => [
                ['مدیریت اسناد', 'مدیریت انواع اسناد، نسخه ها و اصلاحیه ها'],
                ['آرشیو دیجیتال', 'نگهداری و آرشیو دیجیتال اسناد'],
                ['گزارشات مستندات', 'گزارشات متنوع و کاربردی در مورد مستندات'],
            ],
            'مدیریت ریسک' => [
                ['تعریف انواع ریسک', 'تعریف انواع ریسک، فرصت، عدم قطعیت ها'],
                ['کنترل های لازم', 'تعیین نقاط فاقد کنترل های لازم برای حسابرسی داخلی'],
                ['ارزیابی ریسک', 'ارزیابی و اولویت بندی ریسک های شناسایی شده'],
            ],
            'ارزیابی و ممیزی' => [
                ['چک لیست ها', 'تعریف انواع چک لیست ها'],
                ['درخت های ارزیابی', 'ایجاد درخت های ارزیابی'],
                ['برنامه ریزی ارزیابی', 'مدیریت برنامه ریزی ارزیابی میدانی'],
            ],
            'نقلیه' => [
                ['مدیریت خودروها', 'مدیریت خودروهای داخلی و خارج از سازمان'],
                ['هزینه ها', 'ثبت و گزارش گیری از تمامی هزینه ها'],
                ['تعمیرات', 'پیگیری تعمیرات و نگهداری خودروها'],
            ],
            'اسکان' => [
                ['مدیریت مهمان سراها', 'مدیریت هزینه های مالی مهمان سراها و خانه های سازمانی'],
                ['رزرواسیون', 'سیستم رزرواسیون آنلاین'],
                ['گزارش هزینه ها', 'گزارش هزینه های اسکان'],
            ],
            'تغذیه' => [
                ['سفارشات غذا', 'مدیریت در سفارشات غذا'],
                ['قراردادها', 'قرارداد های سازمان با مراکز توزیع'],
                ['گزارشات', 'گزارشات مربوطه از سفارشات و هزینه ها'],
            ],
            'خدمات' => [
                ['رزرواسیون بلیط', 'تمامی خدمات پرسنل از جمله رزرواسیون بلیط'],
                ['هواپیما و قطار', 'رزرواسیون بلیط هواپیما، قطار و سایر وسایل نقلیه'],
                ['پیگیری درخواست ها', 'پیگیری درخواست های خدماتی پرسنل'],
            ],
            'کارتابل' => [
                ['مشاهده امور', 'مشاهده، رسیدگی و پیگیری امور جاری پرسنل'],
                ['نام ها و یادداشت ها', 'مدیریت نام ها، یادداشت ها، یادآوری ها'],
                ['ارجاعات', 'پیگیری ارجاعات و وظایف محوله'],
            ],
        ];
        return $features[$title] ?? [
            ['ویژگی اول', 'توضیحات ویژگی اول این خدمت'],
            ['ویژگی دوم', 'توضیحات ویژگی دوم این خدمت'],
            ['ویژگی سوم', 'توضیحات ویژگی سوم این خدمت'],
        ];
    }

    private function seedData() {
        $check = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($check == 0) {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'مدیر سایت', 'info@mymana-co.ir', 'admin']);
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        if ($check == 0) {
            $defaults = [
                ['site_name', 'شرکت مدرن اندیشان نوین ابتکار'],
                ['site_description', 'مانا حساب - شرکت مدرن اندیشان نوین ابتکار'],
                ['site_email', 'info@mymana-co.ir'],
                ['site_phone', '09158914480'],
                ['site_address', 'مشهد، شهرک غرب، بلوار حسابی، حسابی 36، پلاک 25، واحد 11'],
                ['site_logo', ''],
                ['site_favicon', ''],
                ['facebook', '#'],
                ['twitter', '#'],
                ['linkedin', '#'],
                ['instagram', '#'],
                ['about_text', 'شرکت مدرن اندیشان نوین ابتکار در سال 1397 با هدف پیشبرد فعالیت های مدیریتی و سیستم های مبتنی بر منابع سازمانی با شماره ثبت 65974 تاسیس گردید. شرکت مدرن اندیشان نوین ابتکار (.MANA CO) با مدیریت آقای مهندس هادی زاده و همکاری متخصصین مجرب در امر تولید نرم افزار و مشاوره در سیستم های مدیریتی و پرسنلی با محصولات ORACLE فعالیت می نماید'],
                ['strategy_text', 'امروزه بسياري از سازمان ها جهت انسجام بخشي به فعاليت ها و وظايف خود اقدام به تهيه چشم انداز و برنامه استراتژيك مي نمايند . براي اين برنامه هاي استراتژيك يك سري شاخص ها و اهدافي تعريف مي شود كه طي دوره هاي زماني مشخص اين اهداف و شاخص ها پايش مي گردند هدف از این سامانه ايجاد مديريت هوشمند جهت ساماندهي فعاليت هاي سازمان و جهت دهي آنها به منظور هر چه بيشتر هدفمند كردن آنها در راستاي اهداف سازمان مي باشد. انتظار مي رود با راه اندازي درست اين سامانه بهره وري در سازمان ها حداقل دو برابر شود.'],
                ['testimonial_text', 'پیرو تصمیم هیات مدیره جهت تکمیل و توسعه بخش فنی و پشتیبانی،این شرکت آماده همکاری وعقد قرارداد با متخصصین و علاقه مندان به توسعه سیستم های مبتنی براوراکل می باشد.'],
                ['testimonial_author', 'مدیریت مدرن اندیشان نوین ابتکار'],
                ['copyright', 'کلیه حقوق این سایت متعلق به شرکت مانا می باشد.'],
                ['seo_meta_title', 'شرکت مدرن اندیشان نوین ابتکار | مانا حساب | سیستم مدیریت یکپارچه'],
                ['seo_meta_description', 'شرکت مدرن اندیشان نوین ابتکار ارائه دهنده نرم افزارهای مدیریتی، سامانه مانا حساب، سیستم مدیریت یکپارچه سازمانی، مشاوره و پیاده سازی سیستم های مبتنی بر Oracle'],
                ['seo_meta_keywords', 'مانا حساب, سیستم مدیریت, نرم افزار مدیریتی, مدرن اندیشان, سامانه مودیان, ERP, Oracle'],
                ['seo_og_image', ''],
                ['seo_robots', 'index, follow'],
                ['seo_google_analytics', ''],
                ['seo_schema_org', '1'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
        if ($check == 0) {
            $services = [
                ['عمومی', 'تنظیمات کاری قدرتمند برای پرسنل، تقویم کاری، ارسال پیام کوتاه، ایمیل برای یادآوری موارد مورد نظر و ...', 'fas fa-users-cog', 'عمومی'],
                ['برنامه عملیاتی', 'تعریف انواع برنامه های عملیاتی شرکت، گزارش گیری از برنامه های عملیاتی، استفاده از ربات های ناظر و ...', 'fas fa-tasks', 'برنامه-عملیاتی'],
                ['شاخص', 'مدیریت انواع اسناد، نسخه ها و اصلاحیه ها. گزارش های متنوع و کاربردی در مورد انواع آن ها و ...', 'fas fa-chart-bar', 'شاخص'],
                ['جلسات', 'نظامی قوی در تعریف کمیته ها و اعضاء تنظیم جلسات، ثبت صورت جلسات، نظر سنجی، گزارشات متنوع و ...', 'fas fa-calendar-check', 'جلسات'],
                ['مستندات', 'مدیریت انواع اسناد، نسخه ها و اصلاحیه ها. گزارشات متنوع و کاربردی در مورد انواع آن ها و ...', 'fas fa-folder-open', 'مستندات'],
                ['مدیریت ریسک', 'تعریف انواع ریسک، فرصت، عدم قطعیت ها، تعیین نقاط فاقد کنترل های لازم برای حسابرسی داخلی و ...', 'fas fa-shield-alt', 'مدیریت-ریسک'],
                ['ارزیابی و ممیزی', 'مرکز تعریف انواع چک لیست ها و درخت های ارزیابی، مدیریت برنامه ریزی ارزیابی میدانی و ...', 'fas fa-clipboard-check', 'ارزیابی-و-ممیزی'],
                ['نقلیه', 'مدیریت خودروهای داخلی و خارج از سازمان که در اختیار سازمان می باشندو در آن تمامی هزینه ها و ...', 'fas fa-car', 'نقلیه'],
                ['اسکان', 'امکان مدیریت هزینه های مالی مهمان سراها و خانه های سازمانی به همراه رزرواسیون ...', 'fas fa-hotel', 'اسکان'],
                ['تغذیه', 'مدیریت در سفارشات غذا و قرارداد های سازمان با مراکز توزیع به همراه گزارشات مربوطه', 'fas fa-utensils', 'تغذیه'],
                ['خدمات', 'تمامی خدمات پرسنل از جمله رزرواسیون بلیط (هواپیما، قطارو ..) در این قسمت قرار دارد...', 'fas fa-concierge-bell', 'خدمات'],
                ['کارتابل', 'مشاهده، رسیدگی و پیگیری امور جاری پرسنل از جمله نام ها، یادداشت ها، یادآوری ها و ....', 'fas fa-inbox', 'کارتابل'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO services (title, description, short_description, detail_content, icon, slug, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $i => $s) {
                $detail = '<ul class="service-features">';
                $features = $this->getServiceFeatures($s[0]);
                foreach ($features as $f) {
                    $detail .= '<li><strong>' . $f[0] . ':</strong> ' . $f[1] . '</li>';
                }
                $detail .= '</ul>';
                $stmt->execute([$s[0], $s[1], $s[1], $detail, $s[2], $s[3], $i]);
            }
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM counter_items")->fetchColumn();
        if ($check == 0) {
            $counters = [
                ['سال تجربه', 7, '+', 'a-icon-calendar'],
                ['مشتریان راضی', 1000, 'K+', 'a-icon-user'],
                ['پروژه های انجام شده', 50, '+', 'a-icon-briefcase'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO counter_items (title, value, suffix, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
            foreach ($counters as $i => $c) {
                $stmt->execute([$c[0], $c[1], $c[2], $c[3], $i]);
            }
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM sliders")->fetchColumn();
        if ($check == 0) {
            $sliders = [
                ['مانا حساب', 'شرکت مدرن اندیشان نوین ابتکار', 'شروع کنید!', '/services.php', ''],
                ['مدرن اندیشان نوین ابتکار', 'گزینه ها و ویژگی های ما را بررسی کنید', 'خدمات ما', '/services.php', ''],
                ['خدمات ما', 'زیر سیستم های ما را بررسی کنید', 'مشاهده', '/services.php', ''],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO sliders (title, subtitle, description, link, btn_text, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            foreach ($sliders as $i => $s) {
                $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $i]);
            }
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
        if ($check == 0) {
            $testimonials = [
                ['مدیریت مدرن اندیشان نوین ابتکار', 'مدیریت', 'پیرو تصمیم هیات مدیره جهت تکمیل و توسعه بخش فنی و پشتیبانی،این شرکت آماده همکاری وعقد قرارداد با متخصصین و علاقه مندان به توسعه سیستم های مبتنی براوراکل می باشد.'],
                ['هادی پناهی', 'مشتری', 'لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است.'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO testimonials (name, role, content, status) VALUES (?, ?, ?, 1)");
            foreach ($testimonials as $t) {
                $stmt->execute($t);
            }
        }

        $check = $this->pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
        if ($check == 0) {
            // Main menu items (parent_id = 0)
            $mainItems = [
                ['صفحه اصلی', '/', '_self', 'fas fa-home', 0],
                ['درباره ما', '/about.php', '_self', 'fas fa-info-circle', 1],
                ['خدمات', '#services-submenu', '_self', 'fas fa-cogs', 2],
                ['وبلاگ', '/blog.php', '_self', 'fas fa-newspaper', 3],
                ['تماس با ما', '/contact.php', '_self', 'fas fa-phone', 4],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO menu_items (title, url, target, icon, sort_order, parent_id, status) VALUES (?, ?, ?, ?, ?, 0, 1)");
            foreach ($mainItems as $item) {
                $stmt->execute([$item[0], $item[1], $item[2], $item[3], $item[4]]);
            }

            // Services submenu (parent_id = 3, which is the "خدمات" item)
            $subItems = [
                ['دموی آنلاین نرم افزار EMS', '#', '_blank', '', 0],
                ['سامانه مشتریان', '#', '_blank', '', 1],
                ['سامانه خدماتی بیمه ایران (سرور 1)', '#', '_blank', '', 2],
                ['سامانه خدماتی بیمه ایران (سرور2)', '#', '_blank', '', 3],
                ['نرم افزار مانا حساب (سرور 1)', '#', '_blank', '', 4],
                ['نرم افزار مانا حساب (سرور پشتیبان)', '#', '_blank', '', 5],
                ['عضویت در مانا حساب', '#', '_blank', '', 6],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO menu_items (title, url, target, icon, sort_order, parent_id, status) VALUES (?, ?, ?, ?, ?, 3, 1)");
            foreach ($subItems as $item) {
                $stmt->execute([$item[0], $item[1], $item[2], $item[3], $item[4]]);
            }
        }

        // Seed languages
        $check = $this->pdo->query("SELECT COUNT(*) FROM languages")->fetchColumn();
        if ($check == 0) {
            $languages = [
                ['fa', 'Persian', 'فارسی', 'rtl', '🇮🇷', 0, 1, 1],
                ['en', 'English', 'English', 'ltr', '🇬🇧', 1, 0, 1],
                ['ar', 'Arabic', 'العربية', 'rtl', '🇸🇦', 2, 0, 1],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO languages (code, name, native_name, direction, flag, sort_order, is_default, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($languages as $lang) {
                $stmt->execute($lang);
            }
        }

        // Seed translations for English
        $check = $this->pdo->query("SELECT COUNT(*) FROM translations WHERE lang_code = 'en'")->fetchColumn();
        if ($check == 0) {
            $enTranslations = [
                ['home', 'Home'],
                ['about_us', 'About Us'],
                ['services', 'Services'],
                ['blog', 'Blog'],
                ['contact_us', 'Contact Us'],
                ['site_title', 'Modern ThinkersNovin Entekhab Co.'],
                ['site_description', 'Mana Hesab - Modern ThinkersNovin Entekhab Company'],
                ['read_more', 'Read More'],
                ['contact_info', 'Contact Information'],
                ['address', 'Address'],
                ['email', 'Email'],
                ['phone', 'Phone'],
                ['useful_links', 'Useful Links'],
                ['latest_posts', 'Latest Posts'],
                ['copyright', 'All rights reserved.'],
                ['our_services', 'Our Services'],
                ['explore_subsystems', 'Explore our subsystems'],
                ['what_we_do', 'What We Do'],
                ['about_strategic_management', 'About Strategic Management'],
                ['our_company', 'Our Company'],
                ['upgrade_business', 'Upgrade Your Business'],
                ['years_experience', 'Years Experience'],
                ['satisfied_clients', 'Satisfied Clients'],
                ['completed_projects', 'Completed Projects'],
                ['testimonials', 'Testimonials'],
                ['our_blog', 'Our Blog'],
                ['view_articles', 'View Our Articles'],
                ['search', 'Search'],
                ['search_placeholder', 'Search in...'],
                ['send_message', 'Send Message'],
                ['full_name', 'Full Name'],
                ['subject', 'Subject'],
                ['message', 'Message'],
                ['contact_form', 'Contact Form'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO translations (lang_code, trans_key, trans_value) VALUES (?, ?, ?)");
            foreach ($enTranslations as $t) {
                $stmt->execute(['en', $t[0], $t[1]]);
            }
        }

        // Seed translations for Arabic
        $check = $this->pdo->query("SELECT COUNT(*) FROM translations WHERE lang_code = 'ar'")->fetchColumn();
        if ($check == 0) {
            $arTranslations = [
                ['home', 'الرئيسية'],
                ['about_us', 'من نحن'],
                ['services', 'الخدمات'],
                ['blog', 'المدونة'],
                ['contact_us', 'اتصل بنا'],
                ['site_title', 'شركة المبدعين الحديثين للابتكار'],
                ['site_description', 'مانا حساب - شركة المبدعين الحديثين للابتكار'],
                ['read_more', 'اقرأ المزيد'],
                ['contact_info', 'معلومات الاتصال'],
                ['address', 'العنوان'],
                ['email', 'البريد الإلكتروني'],
                ['phone', 'الهاتف'],
                ['useful_links', 'روابط مفيدة'],
                ['latest_posts', 'آخر المقالات'],
                ['copyright', 'جميع الحقوق محفوظة.'],
                ['our_services', 'خدماتنا'],
                ['search', 'بحث'],
                ['send_message', 'إرسال رسالة'],
                ['full_name', 'الاسم الكامل'],
                ['message', 'الرسالة'],
            ];
            $stmt = $this->pdo->prepare("INSERT INTO translations (lang_code, trans_key, trans_value) VALUES (?, ?, ?)");
            foreach ($arTranslations as $t) {
                $stmt->execute(['ar', $t[0], $t[1]]);
            }
        }
    }
}

$db = Database::getInstance();
