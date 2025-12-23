<?php
// PHẦN XỬ LÝ SERVER (PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    error_reporting(0); 

    // Đảm bảo tên database khớp với phpMyAdmin của bạn (its_db_demo hoặc its_demo_db)
    $conn = new mysqli("localhost", "root", "", "its_db_demo");

    if ($conn->connect_error) {
        echo json_encode(["status" => "Error", "message" => "Lỗi kết nối MySQL!"]);
        exit;
    }

    $code = $_POST['code'] ?? '';

    // Kiểm tra mã trong nhật ký để xác định độ tin cậy
    $check = $conn->query("SELECT id FROM command_logs WHERE signal_code = '$code'");

    if ($check->num_rows > 0) {
        // Tấn công phát lại (Replay Attack) xảy ra khi mã bị sử dụng lại
        $status = "Attack";
        $msg = "CẢNH BÁO: Phát hiện tấn công phát lại (Replay Attack)!";
    } else {
        // Lệnh hợp lệ được lưu vào DB để ngăn chặn tấn công sau này
        $status = "Valid";
        $msg = "Xác thực thành công. Lệnh hợp lệ.";
        $conn->query("INSERT INTO command_logs (signal_code, status) VALUES ('$code', '$status')");
    }

    echo json_encode(["code" => $code, "status" => $status, "message" => $msg]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>ITS Security Monitoring</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        .btn-box { display: flex; gap: 15px; margin-bottom: 25px; }
        button { flex: 1; padding: 15px; border: none; border-radius: 8px; cursor: pointer; color: white; font-weight: bold; font-size: 16px; transition: 0.3s; }
        .btn-green { background: #2ecc71; }
        .btn-red { background: #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #eee; text-align: left; }
        .attack-row { background: #fff5f5; color: #c0392b; font-weight: bold; }
        .valid-row { background: #fafff5; color: #27ae60; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Hệ thống ITS - Giám sát An ninh Mạng</h2>
        <p>Mô phỏng bảo vệ phương tiện khỏi các hình thức tấn công gây nhiễu và phát lại.</p>
        
        <div class="btn-box">
            <button class="btn-green" onclick="simulate('valid')">Người dùng: Mở khóa xe</button>
            <button class="btn-red" onclick="simulate('replay')">Hacker: Phát mã ngẫu nhiên</button>
        </div>

        <table>
            <thead>
                <tr style="background: #f8f9fa;">
                    <th>Mã tín hiệu (Signal)</th>
                    <th>Trạng thái an ninh</th>
                    <th>Phân tích hệ thống</th>
                </tr>
            </thead>
            <tbody id="logBody"></tbody>
        </table>
    </div>

    <script>
        // Mảng lưu trữ mã trong bộ nhớ (không hiển thị ra giao diện)
        let signalVault = [];

        function simulate(type) {
            let signal = "";

            if (type === 'valid') {
                // Tạo mã ngẫu nhiên mới
                signal = "SIG-" + Math.random().toString(36).substr(2, 8).toUpperCase();
                signalVault.push(signal); // Hacker "nghe trộm" và lưu vào bộ nhớ
            } else {
                if (signalVault.length === 0) {
                    alert("Hacker chưa thu thập được mã nào!");
                    return;
                }
                // Hacker chọn ngẫu nhiên một mã từ danh sách đã đánh cắp
                const randomIndex = Math.floor(Math.random() * signalVault.length);
                signal = signalVault[randomIndex];
            }

            let fd = new FormData();
            fd.append('code', signal);

            fetch('index.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                const row = `<tr class="${data.status === 'Attack' ? 'attack-row' : 'valid-row'}">
                                <td>${data.code}</td>
                                <td>${data.status}</td>
                                <td>${data.message}</td>
                             </tr>`;
                document.getElementById('logBody').innerHTML = row + document.getElementById('logBody').innerHTML;
            })
            .catch(err => alert("Lỗi! Kiểm tra kết nối Database."));
        }
    </script>
</body>
</html>