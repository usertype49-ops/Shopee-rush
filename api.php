<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

// ==========================================
// 🔑 您的 Google Gemini API Key
$GEMINI_API_KEY = 'AQ.Ab8RN6I4xQF8ZzYqBMrjLFY0qdPaDeW9oBuWmu7klnbzCnKEXA'; 
// ==========================================

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_menu':
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY type, id DESC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'add_menu':
        $title = $_POST['title'];
        $url = $_POST['url'];
        $type = $_POST['type'];
        $stmt = $pdo->prepare("INSERT INTO menu_items (title, url, type) VALUES (?, ?, ?)");
        $stmt->execute([$title, $url, $type]);
        echo json_encode(["status" => "success"]);
        break;
        
    case 'edit_menu':
        $id = $_POST['id'];
        $title = $_POST['title'];
        $url = $_POST['url'];
        $type = $_POST['type'];
        $stmt = $pdo->prepare("UPDATE menu_items SET title = ?, url = ?, type = ? WHERE id = ?");
        $stmt->execute([$title, $url, $type, $id]);
        echo json_encode(["status" => "success"]);
        break;

    case 'delete_menu':
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success"]);
        break;

    // --- 修改：只抓取前端傳入的「當天日期」班表 ---
    case 'get_schedule':
        $target_date = $_GET['date'] ?? date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM shift_schedule WHERE person_name IN ('林義為', '潘俞蓁') AND shift_date = ? ORDER BY person_name ASC");
        $stmt->execute([$target_date]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'scan_photos':
        $dir = __DIR__ . '/photos/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $files = array_diff(scandir($dir), array('.', '..'));
        $unprocessed = [];
        
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $stmt = $pdo->prepare("SELECT filename FROM processed_photos WHERE filename = ?");
                $stmt->execute([$file]);
                if (!$stmt->fetch()) {
                    $unprocessed[] = $file;
                }
            }
        }
        echo json_encode(array_values($unprocessed));
        break;

    case 'process_photo_ai':
        $filename = $_POST['filename'] ?? '';
        $filepath = __DIR__ . '/photos/' . $filename;

        if (empty($filename) || !file_exists($filepath)) {
            echo json_encode(["status" => "error", "message" => "找不到圖片檔案。"]);
            exit;
        }

        if (empty($GEMINI_API_KEY)) {
            echo json_encode(["status" => "error", "message" => "尚未設定 GEMINI_API_KEY"]);
            exit;
        }

        $image_data = base64_encode(file_get_contents($filepath));
        $mime_type = mime_content_type($filepath);
        $current_year = date("Y");

        $prompt = "這是一張排班表圖片。請精準找出「林義為」與「潘俞蓁」兩人的排班資訊。
請直接回傳純 JSON 陣列，不要包含 Markdown 標記或其他文字。
格式：[{\"date\": \"YYYY-MM-DD\", \"name\": \"員工姓名\", \"shift\": \"班表內容\"}]
規則：
1. 日期請結合表頭的月日，並預設年份為 {$current_year}，格式必須為 YYYY-MM-DD。
2. 若班表為「休」、「指休」、「公」或空白，請將 shift 統一設為「休假」。
3. 若有排班，請完整擷取地點與時間，例如「愛國/四維 1900-2200」或「中洲/光華 0800~1100」。
4. 只需回傳林義為與潘俞蓁的資料，無資料則回傳 []。";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data' => $image_data
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $GEMINI_API_KEY;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            echo json_encode(["status" => "error", "message" => "網路連線錯誤: " . $err]);
            exit;
        }

        $res_data = json_decode($response, true);
        
        if (isset($res_data['error'])) {
             echo json_encode(["status" => "error", "message" => "AI API 拒絕: " . $res_data['error']['message']]);
             exit;
        }

        $text_response = $res_data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
        $text_response = trim(str_replace(['```json', '```'], '', $text_response));
        $schedule_data = json_decode($text_response, true);

        if (!is_array($schedule_data)) {
             echo json_encode(["status" => "error", "message" => "AI 回傳格式異常"]);
             exit;
        }

        $inserted_count = 0;
        foreach ($schedule_data as $row) {
            if (isset($row['date'], $row['name'], $row['shift'])) {
                $detail = $row['shift'];
                
                if ($detail !== '休假') {
                    if (preg_match('/(05|06|07|08|09|10|11|12)\d{2}/', $detail)) {
                        $detail = '早班 ' . $detail;
                    } elseif (preg_match('/(16|17|18|19|20|21|22|23|00)\d{2}/', $detail)) {
                        $detail = '晚班 ' . $detail;
                    } else {
                        $detail = '有班 ' . $detail;
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO shift_schedule (shift_date, person_name, shift_detail) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE shift_detail = ?");
                $stmt->execute([$row['date'], $row['name'], $detail, $detail]);
                $inserted_count++;
            }
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO processed_photos (filename) VALUES (?)");
        $stmt->execute([$filename]);

        echo json_encode([
            "status" => "success", 
            "inserted_records" => $inserted_count
        ]);
        break;
}
?>
