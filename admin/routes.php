<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../config/db.php";
include_once "../include/core.php";

header('Content-Type: application/json');

// ---------------------------------------------------------
// ADMIN GUARD
// Every action below requires a logged-in admin session.
// ---------------------------------------------------------
if(!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])){
    echo json_encode(["statusCode" => 401, "message" => "Not authorized."]);
    exit;
}

$data = new core($conn);
$adminId = $_SESSION['user_id'];


// ---------------------------------------------------------
// Shared image-upload helper.
// Accepts a $_FILES[...] entry, validates it, stores it
// under assets/img/uploads/ and returns the path to save
// in the database (relative to the site root, so it works
// from index.php / about.php).
// ---------------------------------------------------------
function pfms_handle_upload($file)
{
    if(!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE){
        return [null, null];
    }

    if($file['error'] !== UPLOAD_ERR_OK){
        return [null, "Upload failed. Please try again."];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if(!isset($allowed[$mime])){
        return [null, "Please upload a JPG, PNG, GIF or WEBP image."];
    }

    if($file['size'] > 5 * 1024 * 1024){
        return [null, "Image must be under 5MB."];
    }

    $ext      = $allowed[$mime];
    $filename = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir  = __DIR__ . '/../assets/img/uploads/';
    $destPath = $destDir . $filename;

    if(!is_dir($destDir)){
        mkdir($destDir, 0755, true);
    }

    if(!move_uploaded_file($file['tmp_name'], $destPath)){
        return [null, "Could not save the uploaded image."];
    }

    // Path stored in DB / rendered from the site root
    return ['assets/img/uploads/' . $filename, null];
}


$type = $_POST['type'] ?? null;


// ===========================================================
// TYPE 1 — Update text content (home + about pages)
// ===========================================================
if($type == 1){

    $fields = [
        'site_name', 'hero_title', 'hero_subtitle', 'footer_text',
        'about_hero_title', 'about_hero_text1', 'about_hero_text2',
        'about_team_title', 'about_team_intro'
    ];

    $pairs = [];
    foreach($fields as $f){
        if(isset($_POST[$f])){
            $pairs[$f] = trim($_POST[$f]);
        }
    }

    if(empty($pairs)){
        echo json_encode(["statusCode" => 201, "message" => "Nothing to update."]);
        exit;
    }

    try{
        $data->update_settings($pairs);
        echo json_encode(["statusCode" => 200, "message" => "Content updated."]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to save content."]);
    }

    exit;
}


// ===========================================================
// TYPE 2 — Upload / replace the header logo
// ===========================================================
if($type == 2){

    [$path, $err] = pfms_handle_upload($_FILES['logo_image'] ?? null);

    if($err){
        echo json_encode(["statusCode" => 201, "message" => $err]);
        exit;
    }

    if(!$path){
        echo json_encode(["statusCode" => 201, "message" => "Please choose an image to upload."]);
        exit;
    }

    try{
        $data->update_settings(['logo_image' => $path]);
        echo json_encode(["statusCode" => 200, "message" => "Logo updated.", "path" => $path]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to save logo."]);
    }

    exit;
}


// ===========================================================
// TYPE 3 — Upload / replace the homepage hero image
// ===========================================================
if($type == 3){

    [$path, $err] = pfms_handle_upload($_FILES['hero_image'] ?? null);

    if($err){
        echo json_encode(["statusCode" => 201, "message" => $err]);
        exit;
    }

    if(!$path){
        echo json_encode(["statusCode" => 201, "message" => "Please choose an image to upload."]);
        exit;
    }

    try{
        $data->update_settings(['hero_image' => $path]);
        echo json_encode(["statusCode" => 200, "message" => "Header image updated.", "path" => $path]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to save header image."]);
    }

    exit;
}


// ===========================================================
// TYPE 4 — Add a team member (About page)
// ===========================================================
if($type == 4){

    $name      = trim($_POST['name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $role      = trim($_POST['role'] ?? 'Team Member');

    if(empty($name)){
        echo json_encode(["statusCode" => 201, "message" => "Name is required."]);
        exit;
    }

    [$photo, $err] = pfms_handle_upload($_FILES['photo'] ?? null);

    if($err){
        echo json_encode(["statusCode" => 201, "message" => $err]);
        exit;
    }

    try{
        $id = $data->create_team_member($name, $studentId, $role ?: 'Team Member', $photo, 99);
        echo json_encode(["statusCode" => 200, "message" => "Team member added.", "id" => $id]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to add team member."]);
    }

    exit;
}


// ===========================================================
// TYPE 5 — Update a team member
// ===========================================================
if($type == 5){

    $memberId  = $_POST['member_id'] ?? null;
    $name      = trim($_POST['name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $role      = trim($_POST['role'] ?? 'Team Member');

    if(empty($memberId) || empty($name)){
        echo json_encode(["statusCode" => 201, "message" => "Name is required."]);
        exit;
    }

    [$photo, $err] = pfms_handle_upload($_FILES['photo'] ?? null);

    if($err){
        echo json_encode(["statusCode" => 201, "message" => $err]);
        exit;
    }

    try{
        $data->update_team_member($memberId, $name, $studentId, $role ?: 'Team Member', $photo);
        echo json_encode(["statusCode" => 200, "message" => "Team member updated."]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to update team member."]);
    }

    exit;
}


// ===========================================================
// TYPE 6 — Delete a team member
// ===========================================================
if($type == 6){

    $memberId = $_POST['member_id'] ?? null;

    if(empty($memberId)){
        echo json_encode(["statusCode" => 201, "message" => "Missing team member."]);
        exit;
    }

    try{
        $data->delete_team_member($memberId);
        echo json_encode(["statusCode" => 200, "message" => "Team member removed."]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to remove team member."]);
    }

    exit;
}


// ===========================================================
// TYPE 7 — Toggle a user's active status
// ===========================================================
if($type == 7){

    $userId = $_POST['user_id'] ?? null;
    $active = $_POST['active'] ?? null;

    if(empty($userId) || $active === null){
        echo json_encode(["statusCode" => 201, "message" => "Missing data."]);
        exit;
    }

    if((int)$userId === (int)$adminId && (int)$active === 0){
        echo json_encode(["statusCode" => 202, "message" => "You can't deactivate your own account."]);
        exit;
    }

    try{
        $data->set_user_active($userId, (int)$active === 1);
        echo json_encode(["statusCode" => 200, "message" => "User updated."]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to update user."]);
    }

    exit;
}


// ===========================================================
// TYPE 8 — Toggle a user's admin status
// ===========================================================
if($type == 8){

    $userId = $_POST['user_id'] ?? null;
    $admin  = $_POST['admin'] ?? null;

    if(empty($userId) || $admin === null){
        echo json_encode(["statusCode" => 201, "message" => "Missing data."]);
        exit;
    }

    if((int)$userId === (int)$adminId && (int)$admin === 0){
        echo json_encode(["statusCode" => 202, "message" => "You can't remove your own admin access."]);
        exit;
    }

    try{
        $data->set_user_admin($userId, (int)$admin === 1);
        echo json_encode(["statusCode" => 200, "message" => "User updated."]);
    }catch(\Throwable $th){
        echo json_encode(["statusCode" => 500, "message" => "Unable to update user."]);
    }

    exit;
}


echo json_encode(["statusCode" => 404, "message" => "Unknown action."]);

?>
