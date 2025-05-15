<?php
$uploadDir = 'uploads/';
$uploadFile = $uploadDir . basename($_FILES['file']['name']);

// 检查文件是否上传成功
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
    echo "成功上传文件: " . basename($_FILES['file']['name']);
} else {
    echo "文件上传失败";
}
?>