<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Tên database phải là its_db_demo theo đúng file SQL của bạn
    $conn = new mysqli("localhost", "root", "", "its_db_demo");

    // Kiểm tra kết nối và báo lỗi cụ thể
    if ($conn->connect_error) {
        echo json_encode(["status" => "Error", "message" => "Kết nối thất bại: " . $conn->connect_error]);
        exit;
    }

    $code = $_POST['code'] ?? '';

    // Truy vấn để phát hiện tấn công phát lại (Replay Attack) [cite: 11, 12]
    $check = $conn->query("SELECT id FROM command_logs WHERE signal_code = '$code'");

    if ($check->num_rows > 0) {
        // Nếu mã đã tồn tại trong nhật ký [cite: 13, 17]
        $status = "Attack";
        $msg = "PHÁT HIỆN TẤN CÔNG PHÁT LẠI: Mã lệnh đã được sử dụng!";
    } else {
        // Nếu là mã mới, tiến hành lưu để bảo vệ hệ thống [cite: 14, 25]
        $status = "Valid";
        $msg = "Xác thực thành công. Lệnh hợp lệ.";
        $conn->query("INSERT INTO command_logs (signal_code, status) VALUES ('$code', '$status')");
    }

    echo json_encode(["code" => $code, "status" => $status, "message" => $msg]);
    exit;
}
?>