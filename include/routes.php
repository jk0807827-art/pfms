
<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

include_once "../config/db.php";
include_once "core.php";
include_once "login_process.php";

$data = new core($conn);

// Guest cart is tracked per PHP session id when the visitor is not logged in
if(!session_id()){
    session_start();
}
$guestSessionId = session_id();
$currentUserId  = $_SESSION['user_id'] ?? null;

if(isset($_POST['type'])){

    $type = $_POST['type'];

if($type == 1){

    $login = new login_process($conn);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(empty($email) || empty($password)){

        echo json_encode([
            "statusCode" => 202,
            "message" => "Email and password are required"
        ]);

        exit;
    }


    $user = $login->user_login($email);


    if($user){

        // IMPORTANT:
        // Database column is password_hash
        if(password_verify($password, $user['password_hash'])){

            // Check whether account is active
            if((int)$user['is_active'] !== 1){

                echo json_encode([
                    "statusCode" => 203,
                    "message" => "Your account is inactive"
                ]);

                exit;
            }


            // Create session
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];


            /*
             * ADMIN CHECK
             *
             * Driven by the users.is_admin column, set from the
             * admin panel (Users page) or directly in the database.
             */
            $isAdmin = isset($user['is_admin']) && (int)$user['is_admin'] === 1;

            $_SESSION['is_admin'] = $isAdmin ? 1 : 0;

            if($isAdmin){

                echo json_encode([
                    "statusCode" => 200,
                    "role" => "admin"
                ]);

            }else{

                echo json_encode([
                    "statusCode" => 200,
                    "role" => "customer"
                ]);

            }

        }else{

            echo json_encode([
                "statusCode" => 202,
                "message" => "Invalid Email or Password"
            ]);

        }

    }else{

        echo json_encode([
            "statusCode" => 202,
            "message" => "Invalid Email or Password"
        ]);

    }

    exit;
}

if($type == 2){
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';


    // Basic server-side validation
    if(
        empty($name) ||
        empty($email) ||
        empty($phone) ||
        empty($password)
    ){

        echo json_encode([
            "statusCode" => 201,
            "message" => "All fields are required."
        ]);

        exit;
    }


    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){

        echo json_encode([
            "statusCode" => 201,
            "message" => "Invalid email address."
        ]);

        exit;
    }


    if(strlen($password) < 8){

        echo json_encode([
            "statusCode" => 201,
            "message" => "Password must be at least 8 characters."
        ]);

        exit;
    }


    // -------------------------
    // DATABASE
    // -------------------------

    $core = new core($conn);


    // Check if email already exists
    $existingUser = $core->check_email($email);


    if($existingUser){

        echo json_encode([
            "statusCode" => 202,
            "message" => "An account with this email already exists."
        ]);

        exit;
    }


    // Hash password
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // Insert user
    $userCreated = $core->create_user(
        $name,
        $email,
        $phone,
        $passwordHash
    );


    if($userCreated){

        echo json_encode([
            "statusCode" => 200,
            "message" => "Account created successfully."
        ]);

    }else{

        echo json_encode([
            "statusCode" => 201,
            "message" => "Unable to create account."
        ]);

    }


    exit;
}


 // =====================================================
    // PASSWORD RECOVERY
    // TYPE = 3
    // =====================================================

    if($type == 3){


        // -------------------------
        // GET VALUES
        // -------------------------

        $email = trim(
            $_POST['email'] ?? ''
        );

        $phone = trim(
            $_POST['phone'] ?? ''
        );

        $password =
            $_POST['password'] ?? '';



        // -------------------------
        // VALIDATION
        // -------------------------

        if(
            empty($email) ||
            empty($phone) ||
            empty($password)
        ){

            echo json_encode([

                "statusCode" => 202,

                "message" =>
                    "All fields are required"

            ]);

            exit;

        }



        // -------------------------
        // EMAIL VALIDATION
        // -------------------------

        if(
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ){

            echo json_encode([

                "statusCode" => 202,

                "message" =>
                    "Please enter a valid email address"

            ]);

            exit;

        }



        // -------------------------
        // PASSWORD VALIDATION
        // -------------------------

        if(
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ){

            echo json_encode([

                "statusCode" => 202,

                "message" =>
                    "Password must contain at least 8 characters, including a letter and a number"

            ]);

            exit;

        }



        // -------------------------
        // HASH PASSWORD
        // -------------------------

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );



        // -------------------------
        // RESET PASSWORD
        // -------------------------

        try{


            $result = $data->reset_password(

                $email,

                $phone,

                $passwordHash

            );



            // -------------------------
            // SUCCESS
            // -------------------------

            if(
                $result["status"] === true
            ){

                echo json_encode([

                    "statusCode" => 200,

                    "message" =>
                        "Password changed successfully"

                ]);

            }else{


                // -------------------------
                // USER NOT FOUND
                // -------------------------

                echo json_encode([

                    "statusCode" => 202,

                    "message" =>
                        "User not found"

                ]);

            }


        }catch(\Throwable $th){


            echo json_encode([

                "statusCode" => 500,

                "message" =>
                    "Unable to change password. Please try again."

            ]);

        }


        exit;

    }


    // =====================================================
    // Everything below this point requires an authenticated
    // user (types 4 and up). type 1/2/3 above are the public
    // auth flows (login / register / forgot password).
    // =====================================================

    if((int)$type >= 4 && !$currentUserId){

        echo json_encode([
            "statusCode" => 401,
            "message"    => "Please log in again."
        ]);

        exit;
    }

    $userId = $currentUserId;


    // =====================================================
    // CATEGORIES
    // TYPE 4 = add, 5 = update, 6 = delete
    // =====================================================

    if($type == 4){

        $name = trim($_POST['name'] ?? '');
        $catType = trim($_POST['category_type'] ?? '');

        $allowedTypes = ['income','expense','budget','investment'];

        if(empty($name)){
            echo json_encode(["statusCode" => 201, "message" => "Enter a category name."]);
            exit;
        }

        if(!in_array($catType, $allowedTypes)){
            echo json_encode(["statusCode" => 201, "message" => "Invalid category type."]);
            exit;
        }

        try{

            if($data->category_name_exists($userId, $catType, $name)){
                echo json_encode(["statusCode" => 202, "message" => "That category already exists."]);
                exit;
            }

            $newId = $data->create_category($userId, $name, $catType);

            echo json_encode([
                "statusCode"  => 200,
                "message"     => "Category added.",
                "category_id" => $newId,
                "name"        => $name
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to add category."]);
        }

        exit;
    }


    if($type == 5){

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if(empty($name) || $categoryId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a category name."]);
            exit;
        }

        try{

            $existing = $data->get_category($userId, $categoryId);

            if(!$existing){
                echo json_encode(["statusCode" => 404, "message" => "Category not found."]);
                exit;
            }

            if($data->category_name_exists($userId, $existing['type'], $name, $categoryId)){
                echo json_encode(["statusCode" => 202, "message" => "That category already exists."]);
                exit;
            }

            $data->update_category($userId, $categoryId, $name);

            echo json_encode([
                "statusCode"  => 200,
                "message"     => "Category updated.",
                "category_id" => $categoryId,
                "name"        => $name
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to update category."]);
        }

        exit;
    }


    if($type == 6){

        $categoryId = (int)($_POST['category_id'] ?? 0);

        if($categoryId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid category."]);
            exit;
        }

        try{

            $deleted = $data->delete_category($userId, $categoryId);

            if(!$deleted){
                echo json_encode(["statusCode" => 404, "message" => "Category not found."]);
                exit;
            }

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Category deleted."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to delete category."]);
        }

        exit;
    }



    // =====================================================
    // EXPENSES
    // TYPE 7 = add, 8 = update, 9 = delete
    // =====================================================

    if($type == 7 || $type == 8){

        $expenseId  = (int)($_POST['expense_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $amount     = $_POST['amount'] ?? '';
        $date       = trim($_POST['date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if(!is_numeric($amount) || (float)$amount <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid amount greater than 0."]);
            exit;
        }

        if(empty($date)){
            echo json_encode(["statusCode" => 201, "message" => "Select a date."]);
            exit;
        }

        try{

            if($type == 7){

                $newId = $data->create_expense($userId, $categoryId, $amount, $date, $description);

                echo json_encode([
                    "statusCode" => 200,
                    "message"    => "Expense added.",
                    "expense_id" => $newId
                ]);

            }else{

                if($expenseId <= 0){
                    echo json_encode(["statusCode" => 201, "message" => "Invalid expense record."]);
                    exit;
                }

                if(!$data->get_expense($userId, $expenseId)){
                    echo json_encode(["statusCode" => 404, "message" => "Expense record not found."]);
                    exit;
                }

                $data->update_expense($userId, $expenseId, $categoryId, $amount, $date, $description);

                echo json_encode([
                    "statusCode" => 200,
                    "message"    => "Expense updated."
                ]);

            }

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to save expense."]);
        }

        exit;
    }


    if($type == 9){

        $expenseId = (int)($_POST['expense_id'] ?? 0);

        if($expenseId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid expense record."]);
            exit;
        }

        try{

            $deleted = $data->delete_expense($userId, $expenseId);

            if(!$deleted){
                echo json_encode(["statusCode" => 404, "message" => "Expense record not found."]);
                exit;
            }

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Expense deleted."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to delete expense."]);
        }

        exit;
    }



    // =====================================================
    // INCOME
    // TYPE 10 = add, 11 = update, 12 = delete
    // =====================================================

    if($type == 10 || $type == 11){

        $incomeId   = (int)($_POST['income_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $source     = trim($_POST['source'] ?? '');
        $amount     = $_POST['amount'] ?? '';
        $date       = trim($_POST['date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if(empty($source)){
            echo json_encode(["statusCode" => 201, "message" => "Enter a source."]);
            exit;
        }

        if(!is_numeric($amount) || (float)$amount <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid amount greater than 0."]);
            exit;
        }

        if(empty($date)){
            echo json_encode(["statusCode" => 201, "message" => "Select a date."]);
            exit;
        }

        try{

            if($type == 10){

                $newId = $data->create_income($userId, $categoryId, $source, $amount, $date, $description);

                echo json_encode([
                    "statusCode" => 200,
                    "message"    => "Income added.",
                    "income_id"  => $newId
                ]);

            }else{

                if($incomeId <= 0){
                    echo json_encode(["statusCode" => 201, "message" => "Invalid income record."]);
                    exit;
                }

                if(!$data->get_income_row($userId, $incomeId)){
                    echo json_encode(["statusCode" => 404, "message" => "Income record not found."]);
                    exit;
                }

                $data->update_income($userId, $incomeId, $categoryId, $source, $amount, $date, $description);

                echo json_encode([
                    "statusCode" => 200,
                    "message"    => "Income updated."
                ]);

            }

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to save income."]);
        }

        exit;
    }


    if($type == 12){

        $incomeId = (int)($_POST['income_id'] ?? 0);

        if($incomeId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid income record."]);
            exit;
        }

        try{

            $deleted = $data->delete_income($userId, $incomeId);

            if(!$deleted){
                echo json_encode(["statusCode" => 404, "message" => "Income record not found."]);
                exit;
            }

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Income deleted."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to delete income."]);
        }

        exit;
    }



    // =====================================================
    // BUDGETS
    // TYPE 13 = add/upsert, 14 = update amount, 15 = delete
    // =====================================================

    if($type == 13){

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $month      = trim($_POST['month'] ?? '');
        $amount     = $_POST['amount'] ?? '';

        if($categoryId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Choose a category."]);
            exit;
        }

        if(!preg_match('/^\d{4}-\d{2}$/', $month)){
            echo json_encode(["statusCode" => 201, "message" => "Choose a month."]);
            exit;
        }

        if(!is_numeric($amount) || (float)$amount <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid amount greater than 0."]);
            exit;
        }

        try{

            $data->upsert_budget($userId, $categoryId, $month, $amount);

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Budget saved."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to save budget."]);
        }

        exit;
    }


    if($type == 14){

        $budgetId = (int)($_POST['budget_id'] ?? 0);
        $amount   = $_POST['amount'] ?? '';

        if($budgetId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid budget."]);
            exit;
        }

        if(!is_numeric($amount) || (float)$amount <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid amount greater than 0."]);
            exit;
        }

        try{

            if(!$data->get_budget($userId, $budgetId)){
                echo json_encode(["statusCode" => 404, "message" => "Budget not found."]);
                exit;
            }

            $data->update_budget_amount($userId, $budgetId, $amount);

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Budget updated."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to update budget."]);
        }

        exit;
    }


    if($type == 15){

        $budgetId = (int)($_POST['budget_id'] ?? 0);

        if($budgetId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid budget."]);
            exit;
        }

        try{

            $deleted = $data->delete_budget($userId, $budgetId);

            if(!$deleted){
                echo json_encode(["statusCode" => 404, "message" => "Budget not found."]);
                exit;
            }

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Budget deleted."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to delete budget."]);
        }

        exit;
    }



    // =====================================================
    // INVESTMENTS
    // TYPE 16 = add, 17 = update, 18 = delete
    // =====================================================

    if($type == 16 || $type == 17){

        $investmentId = (int)($_POST['investment_id'] ?? 0);
        $categoryId   = (int)($_POST['category_id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $invested     = $_POST['invested'] ?? '';
        $current      = $_POST['current'] ?? '';
        $date         = trim($_POST['purchase_date'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        if(empty($name)){
            echo json_encode(["statusCode" => 201, "message" => "Enter a name."]);
            exit;
        }

        if(!is_numeric($invested) || (float)$invested <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid amount greater than 0."]);
            exit;
        }

        if(!is_numeric($current) || (float)$current < 0){
            echo json_encode(["statusCode" => 201, "message" => "Enter a valid current value."]);
            exit;
        }

        if(empty($date)){
            echo json_encode(["statusCode" => 201, "message" => "Select a date."]);
            exit;
        }

        try{

            if($type == 16){

                $newId = $data->create_investment($userId, $categoryId, $name, $invested, $current, $date, $notes);

                echo json_encode([
                    "statusCode"     => 200,
                    "message"        => "Investment added.",
                    "investment_id"  => $newId
                ]);

            }else{

                if($investmentId <= 0){
                    echo json_encode(["statusCode" => 201, "message" => "Invalid investment record."]);
                    exit;
                }

                if(!$data->get_investment($userId, $investmentId)){
                    echo json_encode(["statusCode" => 404, "message" => "Investment record not found."]);
                    exit;
                }

                $data->update_investment($userId, $investmentId, $categoryId, $name, $invested, $current, $date, $notes);

                echo json_encode([
                    "statusCode" => 200,
                    "message"    => "Investment updated."
                ]);

            }

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to save investment."]);
        }

        exit;
    }


    if($type == 18){

        $investmentId = (int)($_POST['investment_id'] ?? 0);

        if($investmentId <= 0){
            echo json_encode(["statusCode" => 201, "message" => "Invalid investment record."]);
            exit;
        }

        try{

            $deleted = $data->delete_investment($userId, $investmentId);

            if(!$deleted){
                echo json_encode(["statusCode" => 404, "message" => "Investment record not found."]);
                exit;
            }

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Investment deleted."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to delete investment."]);
        }

        exit;
    }



    // =====================================================
    // SETTINGS
    // TYPE 19 = update profile, 20 = change password
    // =====================================================

    if($type == 19){

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if(empty($name) || empty($email) || empty($phone)){
            echo json_encode(["statusCode" => 201, "message" => "All fields are required."]);
            exit;
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo json_encode(["statusCode" => 201, "message" => "Invalid email address."]);
            exit;
        }

        try{

            if($data->email_taken_by_other($email, $userId)){
                echo json_encode(["statusCode" => 202, "message" => "That email is already in use."]);
                exit;
            }

            $data->update_profile($userId, $name, $email, $phone);

            // Keep the session in sync with the new details
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Profile updated.",
                "name"       => $name,
                "email"      => $email
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to update profile."]);
        }

        exit;
    }


    if($type == 20){

        $current = $_POST['current_password'] ?? '';
        $next    = $_POST['new_password'] ?? '';

        if(empty($current) || empty($next)){
            echo json_encode(["statusCode" => 201, "message" => "All fields are required."]);
            exit;
        }

        if(
            strlen($next) < 8 ||
            !preg_match('/[A-Za-z]/', $next) ||
            !preg_match('/[0-9]/', $next)
        ){
            echo json_encode(["statusCode" => 201, "message" => "Password must be at least 8 characters, including a letter and a number."]);
            exit;
        }

        try{

            $hash = $data->get_password_hash($userId);

            if(!$hash || !password_verify($current, $hash)){
                echo json_encode(["statusCode" => 202, "message" => "Current password is incorrect."]);
                exit;
            }

            $newHash = password_hash($next, PASSWORD_DEFAULT);
            $data->update_password($userId, $newHash);

            echo json_encode([
                "statusCode" => 200,
                "message"    => "Password changed successfully."
            ]);

        }catch(\Throwable $th){
            echo json_encode(["statusCode" => 500, "message" => "Unable to change password."]);
        }

        exit;
    }




}
?>
