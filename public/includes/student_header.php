<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student information
$pdo = get_pdo_connection();
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.profile_image 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - SAMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        #wrapper {
            display: flex;
        }
        
        #sidebar-wrapper {
            width: var(--sidebar-width);
            min-height: 100vh;
            margin-left: calc(-1 * var(--sidebar-width));
            transition: margin .25s ease-out;
            background-color: #2c3e50;
        }
        
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        
        #page-content-wrapper {
            min-width: 100vw;
        }
        
        .sidebar-heading {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
        }
        
        .list-group-item {
            background-color: transparent;
            color: #ecf0f1;
            border: none;
            padding: 0.8rem 1.5rem;
        }
        
        .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .list-group-item.active {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .student-profile {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .student-profile img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
            
            #wrapper.toggled #sidebar-wrapper {
                margin-left: calc(-1 * var(--sidebar-width));
            }
        }
    </style>
</head>
<body>
<div id="wrapper">
