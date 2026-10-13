<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بررسی و نمایش JSON</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 2rem;
        }

        h1 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: #38bdf8;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .section {
            background: #1e293b;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #334155;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            display: inline-block;
            background: #334155;
            color: #38bdf8;
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-weight: 700;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 0.9rem;
            resize: vertical;
            direction: ltr;
            text-align: left;
        }

        textarea:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.15);
        }

        .btn-row {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        button {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button:active {
            transform: scale(0.97);
        }

        .btn-check {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-check:hover {
            background: #0284c7;
        }

        .btn-clear {
            background: #334155;
            color: #94a3b8;
        }

        .btn-clear:hover {
            background: #475569;
        }

        .json-output {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
            direction: ltr;
            text-align: left;
            min-height: 80px;
            max-height: 400px;
            overflow-y: auto;
        }

        .error-msg {
            color: #f87171;
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 0.85rem;
            direction: ltr;
            text-align: left;
        }

        .success-msg {
            color: #4ade80;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #334155;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        th {
            background: #334155;
            color: #38bdf8;
            padding: 0.7rem 1rem;
            text-align: right;
            white-space: nowrap;
            font-weight: 600;
        }

        td {
            padding: 0.6rem 1rem;
            border-top: 1px solid #1e293b;
            background: #0f172a;
            word-break: break-word;
            text-align: right;
            direction: rtl;
        }

        tr:hover td {
            background: #1e293b;
        }

        .no-data {
            text-align: center;
            color: #64748b;
            padding: 2rem;
        }

        .placeholder {
            text-align: center;
            color: #475569;
            padding: 2rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>بررسی، فرمت و نمایش JSON</h1>

    <?php
    $input   = '';
    $jsonOut = '';
    $error   = '';
    $tableData = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_input'])) {
        $input = $_POST['json_input'];
        $trimmed = trim($input);

        if ($trimmed === '') {
            $error = 'لطفاً متنی وارد کنید.';
        } else {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'خطا: ' . json_last_error_msg() . "\n\n" .
                         'کد خطا: ' . json_last_error();
            } else {
                $jsonOut  = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $tableData = $decoded;
            }
        }
    }
    ?>

    <!-- Section 1: Input -->
    <div class="section">
        <div class="section-title">
            <span class="badge">۱</span>
            متن JSON را وارد کنید
        </div>
        <form method="POST">
            <textarea
                name="json_input"
                dir="ltr"
                placeholder='{"name": "Ali", "age": 25, "skills": ["PHP", "MySQL"]}'
            ><?= htmlspecialchars($input) ?></textarea>
            <div class="btn-row">
                <button type="submit" class="btn-check">بررسی</button>
                <button type="button" class="btn-clear" onclick="this.closest('form').reset(); this.closest('form').submit();">پاک کردن</button>
            </div>
        </form>
    </div>

    <!-- Section 2: JSON Output -->
    <div class="section">
        <div class="section-title">
            <span class="badge">۲</span>
            خروجی JSON فرمت شده
        </div>
        <?php if ($error): ?>
            <div class="error-msg"><?= nl2br(htmlspecialchars($error)) ?></div>
        <?php elseif ($jsonOut !== ''): ?>
            <div class="success-msg">JSON معتبر است</div>
            <div class="json-output"><?= htmlspecialchars($jsonOut) ?></div>
        <?php else: ?>
            <div class="placeholder">پس از زدن دکمه بررسی، نتیجه اینجا نمایش داده می‌شود</div>
        <?php endif; ?>
    </div>

    <!-- Section 3: Table -->
    <div class="section">
        <div class="section-title">
            <span class="badge">۳</span>
            نمایش جدولی
        </div>
        <?php if ($tableData !== null): ?>
            <?php
            // Flatten nested structures for table display
            function flattenArray($data, $prefix = '') {
                $result = [];
                foreach ($data as $key => $value) {
                    $fullKey = $prefix ? "$prefix.$key" : $key;
                    if (is_array($value) && !array_is_list($value)) {
                        $result = array_merge($result, flattenArray($value, $fullKey));
                    } elseif (is_array($value)) {
                        $result[$fullKey] = implode(', ', array_map(function($v) {
                            return is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                        }, $value));
                    } else {
                        $result[$fullKey] = $value;
                    }
                }
                return $result;
            }

            if (array_is_list($tableData)) {
                // Array of items
                if (count($tableData) > 0 && is_array($tableData[0])) {
                    // Collect all keys
                    $allKeys = [];
                    foreach ($tableData as $item) {
                        if (is_array($item)) {
                            $flat = flattenArray($item);
                            foreach (array_keys($flat) as $k) {
                                $allKeys[$k] = true;
                            }
                        }
                    }
                    $columns = array_keys($allKeys);
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php foreach ($columns as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableData as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <?php
                                        $flat = is_array($item) ? flattenArray($item) : [$item];
                                        foreach ($columns as $col): ?>
                                            <td><?= htmlspecialchars($flat[$col] ?? '') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else {
                    // Simple list
                    ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>#</th><th>مقدار</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableData as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars(is_scalar($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php }
            } else {
                // Single object
                $flat = flattenArray($tableData);
                ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>کلید</th><th>مقدار</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($flat as $key => $value): ?>
                                <tr>
                                    <td><?= htmlspecialchars($key) ?></td>
                                    <td><?= htmlspecialchars($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="no-data">داده‌ای برای نمایش وجود ندارد</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
