<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSWEB AI 行動管理系統</title>
    <style>
        /* 全域視覺設計 */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            background-color: #f4f7f6; 
            margin: 0; 
            padding: 20px; 
            color: #2c3e50;
        }
        h2 { 
            color: #2c3e50; 
            border-left: 6px solid #3498db; 
            padding-left: 15px; 
            margin-top: 35px;
            margin-bottom: 20px;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
        }
        
        /* 優美快捷選單網格 */
        .menu-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .menu-item-wrapper {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .menu-item-wrapper:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .menu-btn { 
            color: white; 
            padding: 15px 10px; 
            text-decoration: none; 
            text-align: center; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 1.1rem;
            display: block;
            margin-bottom: 15px;
            text-shadow: 0px 1px 2px rgba(0,0,0,0.2);
            transition: opacity 0.2s;
        }
        .menu-btn:active { opacity: 0.8; }
        .menu-btn.line { background: linear-gradient(135deg, #00c300, #009900); }
        .menu-btn.shortcut { background: linear-gradient(135deg, #f39c12, #e67e22); }
        
        /* 按鈕群組 (修改/刪除) */
        .action-group { display: flex; gap: 10px; }
        .action-btn { 
            flex: 1; border: none; padding: 8px; border-radius: 6px; 
            cursor: pointer; font-size: 0.95rem; font-weight: 600; color: white;
        }
        .edit-btn { background: #3498db; }
        .edit-btn:hover { background: #2980b9; }
        .delete-btn { background: #e74c3c; }
        .delete-btn:hover { background: #c0392b; }

        /* 👑 今日專屬排版 (自動調整長度與寬度) */
        .table-responsive {
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            overflow: hidden; /* 取消橫向捲動，因為現在只有兩欄 */
        }
        .daily-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 1.25rem; /* 字體放大更清晰 */
            table-layout: auto; /* 核心：自動根據內容調整適合寬度 */
        }
        .daily-table th, .daily-table td {
            padding: 20px 25px;
            border-bottom: 1px solid #ecf0f1;
            word-break: keep-all; /* 防止文字不當換行 */
        }
        .daily-table th {
            background-color: #f8f9fa;
            color: #34495e;
            font-weight: 700;
        }
        .daily-table tr:last-child td { border-bottom: none; }
        .person-name { font-weight: bold; color: #2c3e50; white-space: nowrap; }
        .shift-detail { width: 100%; /* 讓內容欄位自動佔滿剩餘寬度 */ }

        /* 班別高亮標示 */
        .shift-morning { background-color: #e8f4f8; color: #2980b9; font-weight: 600; } 
        .shift-night { background-color: #fdf2e9; color: #d35400; font-weight: 600; }   
        .shift-off { background-color: #fdedec; color: #c0392b; font-weight: 600; }     
        .shift-none { color: #bdc3c7; font-weight: bold; }

        /* 懸浮按鈕 FAB */
        .fab {
            position: fixed; bottom: 35px; right: 35px; background: #3498db; color: white;
            width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 30px; cursor: pointer; 
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4); border: none; z-index: 1000; 
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .fab:hover { transform: scale(1.1); }
        .fab:active { transform: scale(0.95); }

        /* 隱藏/展開 表單面板 */
        .form-section { 
            display: none; background: white; padding: 25px; border-radius: 12px; 
            margin-bottom: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .form-section h3 { margin-top: 0; color: #2c3e50; display: flex; justify-content: space-between; align-items: center;}
        .close-form { cursor: pointer; color: #95a5a6; font-size: 1.2rem; }
        .form-section input, .form-section select { 
            width: 100%; padding: 14px; margin: 10px 0 20px 0; border: 1px solid #dcdde1; 
            border-radius: 8px; font-size: 1.05rem; box-sizing: border-box; 
        }
        .form-section button { 
            background: #2ecc71; color: white; padding: 14px; border: none; 
            border-radius: 8px; font-size: 1.1rem; cursor: pointer; width: 100%; font-weight: bold; 
        }

        /* 狀態列 */
        #ocr-status { 
            background: #e1f5fe; color: #0277bd; padding: 15px; border-radius: 8px; 
            font-weight: 600; margin-bottom: 25px; border-left: 5px solid #03a9f4; 
            font-size: 1.05rem; display: flex; align-items: center; gap: 10px;
        }
    </style>
</head>
<body>

    <!-- 懸浮按鈕：點擊顯示表單 -->
    <button class="fab" onclick="toggleForm()">➕</button>

    <!-- 預設隱藏的新增/修改表單 -->
    <div class="form-section" id="addForm">
        <h3>
            <span id="form-title">✨ 新增選單項目</span>
            <span class="close-form" onclick="toggleForm()">✖</span>
        </h3>
        <input type="hidden" id="menu_id" value="">
        <input type="text" id="title" placeholder="顯示名稱 (例: 家族 LINE 群、設定捷徑)">
        <input type="text" id="url" placeholder="網址連結 或 Android Intent 路徑">
        <small style="color: #7f8c8d; display:block; margin-top:-15px; margin-bottom:15px; font-size: 0.85rem;">
            💡 提示：LINE請貼網址；安卓捷徑例: intent://#Intent;package=com.android.settings;end;
        </small>
        <select id="type">
            <option value="line">LINE 群組聊天室捷徑</option>
            <option value="shortcut">安卓手機 APP 捷徑</option>
        </select>
        <button onclick="saveMenu()">💾 儲存資料</button>
    </div>

    <h2>🚀 快捷控制選單</h2>
    <div class="menu-grid" id="menu-container"></div>

    <!-- 動態更新標題為當天日期 -->
    <h2 id="schedule-title">📅 今日班表自動同步</h2>
    <div id="ocr-status">⏳ 正在檢查本地目錄 /photos/ 是否有新班表照片...</div>
    <div id="schedule-container"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadMenu();
            loadSchedule();
            autoProcessPhotos(); 
        });

        function toggleForm(isEdit = false) {
            const form = document.getElementById('addForm');
            if(form.style.display === 'block' && !isEdit) {
                form.style.display = 'none';
            } else {
                form.style.display = 'block';
                if(!isEdit) {
                    document.getElementById('menu_id').value = '';
                    document.getElementById('title').value = '';
                    document.getElementById('url').value = '';
                    document.getElementById('form-title').innerText = "✨ 新增選單項目";
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        async function loadMenu() {
            const container = document.getElementById('menu-container');
            try {
                const res = await fetch('api.php?action=get_menu');
                const data = await res.json();
                
                container.innerHTML = '';
                if(data.length === 0) {
                    container.innerHTML = '<div style="color:#7f8c8d;">目前無選單，請點擊右下角 ➕ 新增。</div>';
                    return;
                }

                data.forEach(item => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'menu-item-wrapper';
                    wrapper.innerHTML = `
                        <a href="${item.url}" class="menu-btn ${item.type}" target="_blank">
                            ${item.type === 'line' ? '💬' : '📱'} ${item.title}
                        </a>
                        <div class="action-group">
                            <button class="action-btn edit-btn" onclick="editMenu(${item.id}, '${item.title}', '${item.url}', '${item.type}')">修改</button>
                            <button class="action-btn delete-btn" onclick="deleteMenu(${item.id})">刪除</button>
                        </div>
                    `;
                    container.appendChild(wrapper);
                });
            } catch (e) {
                container.innerHTML = `<div style="color:red;">選單載入失敗</div>`;
            }
        }

        function editMenu(id, title, url, type) {
            document.getElementById('menu_id').value = id;
            document.getElementById('title').value = title;
            document.getElementById('url').value = url;
            document.getElementById('type').value = type;
            document.getElementById('form-title').innerText = "✏️ 修改選單項目";
            toggleForm(true);
        }

        async function saveMenu() {
            const id = document.getElementById('menu_id').value;
            const title = document.getElementById('title').value.trim();
            const url = document.getElementById('url').value.trim();
            const type = document.getElementById('type').value;

            if(!title || !url) return alert('請填寫完整名稱與路徑！');

            const formData = new FormData();
            formData.append('title', title);
            formData.append('url', url);
            formData.append('type', type);

            if(id) {
                formData.append('id', id);
                await fetch('api.php?action=edit_menu', { method: 'POST', body: formData });
            } else {
                await fetch('api.php?action=add_menu', { method: 'POST', body: formData });
            }
            
            toggleForm();
            loadMenu();
        }

        async function deleteMenu(id) {
            if(!confirm('確定要刪除嗎？')) return;
            const formData = new FormData();
            formData.append('id', id);
            await fetch('api.php?action=delete_menu', { method: 'POST', body: formData });
            loadMenu();
        }

        // 👑 載入當天專屬班表 (自動調整寬度)
        async function loadSchedule() {
            const container = document.getElementById('schedule-container');
            const titleEl = document.getElementById('schedule-title');
            
            try {
                // 取得設備當下的 YYYY-MM-DD
                const d = new Date();
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;
                
                // 更新標題
                titleEl.innerHTML = `📅 今日班表 (${todayStr})`;

                // 呼叫 API 並且僅抓取今天的資料
                const res = await fetch(`api.php?action=get_schedule&date=${todayStr}`);
                const data = await res.json();
                
                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = `<div style="color:#7f8c8d; background:white; padding:20px; border-radius:12px; text-align:center; font-size:1.1rem;">今日 (${todayStr}) 尚無班表資料，或尚未排班。</div>`;
                    return;
                }

                // 初始化兩位人員，預設值為 -
                const people = ['林義為', '潘俞蓁'];
                const scheduleMap = { '林義為': '-', '潘俞蓁': '-' };
                
                // 填入 SQL 回傳的當日資料
                data.forEach(item => {
                    if (scheduleMap[item.person_name] !== undefined) {
                        scheduleMap[item.person_name] = item.shift_detail;
                    }
                });

                // 產生自動調整適當寬度的兩欄式表格
                let tableHtml = '<div class="table-responsive"><table class="daily-table">';
                tableHtml += '<thead><tr><th>門市人員</th><th>班表地點與時間</th></tr></thead><tbody>';

                people.forEach(person => {
                    const detail = scheduleMap[person];
                    let shiftClass = 'shift-none';
                    let displayText = detail;
                    
                    if (detail !== '-') {
                        if (detail.includes('早')) shiftClass = 'shift-morning';
                        else if (detail.includes('晚')) shiftClass = 'shift-night';
                        else if (detail.includes('休') || detail.includes('公')) shiftClass = 'shift-off';
                    }
                    
                    tableHtml += `<tr>
                        <td class="person-name">${person}</td>
                        <td class="shift-detail ${shiftClass}">${displayText}</td>
                    </tr>`;
                });

                tableHtml += '</tbody></table></div>';
                container.innerHTML = tableHtml;
            } catch (error) {
                container.innerHTML = `<div style="color:red; background:white; padding:15px; border-radius:12px;">今日班表載入失敗：${error.message}</div>`;
            }
        }

        async function autoProcessPhotos() {
            const statusDiv = document.getElementById('ocr-status');
            try {
                const res = await fetch('api.php?action=scan_photos');
                const photos = await res.json();

                if (Array.isArray(photos) && photos.length > 0) {
                    statusDiv.style.background = "#fff3cd";
                    statusDiv.style.color = "#856404";
                    statusDiv.style.borderLeftColor = "#ffc107";
                    statusDiv.innerText = `🤖 發現 ${photos.length} 張新照片！正在呼叫 Gemini AI 進行解析...`;
                    
                    for (let photo of photos) {
                        const formData = new FormData();
                        formData.append('filename', photo);
                        await fetch('api.php?action=process_photo_ai', { method: 'POST', body: formData });
                    }
                    
                    statusDiv.style.background = "#d4edda";
                    statusDiv.style.color = "#155724";
                    statusDiv.style.borderLeftColor = "#28a745";
                    statusDiv.innerText = "✅ AI 班表辨識完成並已同步至 SQL！";
                    loadSchedule(); 
                } else {
                    statusDiv.style.background = "#e1f5fe";
                    statusDiv.style.color = "#0277bd";
                    statusDiv.style.borderLeftColor = "#03a9f4";
                    statusDiv.innerText = "✔️ 系統為最新狀態，監測資料夾中...";
                }
            } catch (error) {
                statusDiv.innerHTML = "<span style='color:red;'>自動更新異常：" + error.message + "</span>";
            }
            setTimeout(autoProcessPhotos, 30000); 
        }
    </script>
</body>
</html>
