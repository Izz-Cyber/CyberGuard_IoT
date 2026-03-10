<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
<h1>لوحة التحكم</h1>
<p>مرحباً، <?=htmlspecialchars($_SESSION['username'])?></p>
<ul>
    <li><a href="users.php">إدارة المستخدمين</a></li>
    <li><a href="about_edit.php">تعديل صفحة about</a></li>
    <li><a href="logs.php">سجلات الفحص</a></li>
    <li><a href="settings.php">إعدادات (المفتاح العام والبرمبت)</a></li>
    <li><a href="logout.php">خروج</a></li>
</ul>
</body>
</html>
