<?php 

class core{

    var $db;


    function __construct($conn){

        $this->db = $conn;

    }




   public function check_email($email)
    {

        try{


            $sql = "

                SELECT user_id

                FROM users

                WHERE email = :email

                LIMIT 1

            ";


            $statement =
                $this->db->prepare($sql);


            $statement->execute([

                ":email" => $email

            ]);


            return $statement->fetch(
                PDO::FETCH_ASSOC
            );


        }catch(\Throwable $th){

            throw $th;

        }

    }



    // =====================================
    // CREATE USER
    // =====================================

    public function create_user(
        $name,
        $email,
        $phone,
        $passwordHash
    )
    {

        try{


            $sql = "

                INSERT INTO users

                (
                    full_name,
                    email,
                    phone,
                    password_hash,
                    is_active,
                    created_at,
                    updated_at
                )

                VALUES

                (
                    :full_name,
                    :email,
                    :phone,
                    :password_hash,
                    1,
                    NOW(),
                    NOW()
                )

            ";


            $statement =
                $this->db->prepare($sql);


            $statement->execute([

                ":full_name" =>
                    $name,

                ":email" =>
                    $email,

                ":phone" =>
                    $phone,

                ":password_hash" =>
                    $passwordHash

            ]);


            return true;


        }catch(\Throwable $th){

            throw $th;

        }

    }



    // =====================================
    // RESET PASSWORD
    // =====================================

    public function reset_password(
        $email,
        $phone,
        $passwordHash
    )
    {

        try{


            // ---------------------------------
            // FIND USER
            //
            // Email AND phone must belong
            // to the same account.
            // ---------------------------------

            $sql = "

                SELECT user_id

                FROM users

                WHERE email = :email

                AND phone = :phone

                LIMIT 1

            ";


            $statement =
                $this->db->prepare($sql);


            $statement->execute([

                ":email" =>
                    $email,

                ":phone" =>
                    $phone

            ]);


            $user =
                $statement->fetch(
                    PDO::FETCH_ASSOC
                );



            // ---------------------------------
            // USER NOT FOUND
            // ---------------------------------

            if(!$user){

                return [

                    "status" => false,

                    "message" =>
                        "User not found"

                ];

            }



            // ---------------------------------
            // EMAIL + PHONE MATCH
            //
            // CHANGE PASSWORD
            // ---------------------------------

            $sql = "

                UPDATE users

                SET

                    password_hash = :password_hash,

                    updated_at = NOW()

                WHERE user_id = :user_id

            ";


            $statement =
                $this->db->prepare($sql);


            $statement->execute([

                ":password_hash" =>
                    $passwordHash,

                ":user_id" =>
                    $user['user_id']

            ]);



            // ---------------------------------
            // SUCCESS
            // ---------------------------------

            return [

                "status" => true,

                "message" =>
                    "Password changed successfully"

            ];


        }catch(\Throwable $th){

            throw $th;

        }

    }



    // =====================================================
    // =====================================================
    //  CATEGORIES
    // =====================================================
    // =====================================================

    public function get_categories($user_id, $type, $includeArchived = false)
    {
        try{

            $sql = "
                SELECT category_id, user_id, name, type, is_archived, created_at
                FROM categories
                WHERE user_id = :user_id
                AND type = :type
            ";

            if(!$includeArchived){
                $sql .= " AND is_archived = 0 ";
            }

            $sql .= " ORDER BY name ASC ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id" => $user_id,
                ":type"    => $type
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_category($user_id, $category_id)
    {
        try{

            $sql = "
                SELECT category_id, user_id, name, type, is_archived
                FROM categories
                WHERE user_id = :user_id
                AND category_id = :category_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"     => $user_id,
                ":category_id" => $category_id
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function category_name_exists($user_id, $type, $name, $excludeId = null)
    {
        try{

            $sql = "
                SELECT category_id
                FROM categories
                WHERE user_id = :user_id
                AND type = :type
                AND name = :name
            ";

            $params = [
                ":user_id" => $user_id,
                ":type"    => $type,
                ":name"    => $name
            ];

            if($excludeId){
                $sql .= " AND category_id != :excludeId ";
                $params[":excludeId"] = $excludeId;
            }

            $sql .= " LIMIT 1 ";

            $statement = $this->db->prepare($sql);
            $statement->execute($params);

            return (bool) $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function create_category($user_id, $name, $type)
    {
        try{

            $sql = "
                INSERT INTO categories (user_id, name, type, is_archived, created_at)
                VALUES (:user_id, :name, :type, 0, NOW())
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id" => $user_id,
                ":name"    => $name,
                ":type"    => $type
            ]);

            return (int) $this->db->lastInsertId();

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_category($user_id, $category_id, $name)
    {
        try{

            $sql = "
                UPDATE categories
                SET name = :name
                WHERE user_id = :user_id
                AND category_id = :category_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":name"        => $name,
                ":user_id"     => $user_id,
                ":category_id" => $category_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function delete_category($user_id, $category_id)
    {
        try{

            $sql = "
                DELETE FROM categories
                WHERE user_id = :user_id
                AND category_id = :category_id
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"     => $user_id,
                ":category_id" => $category_id
            ]);

            return $statement->rowCount() > 0;

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  EXPENSES
    // =====================================================
    // =====================================================

    public function get_expenses($user_id, $month = null)
    {
        try{

            $sql = "
                SELECT
                    e.expense_id,
                    e.category_id,
                    c.name AS category_name,
                    e.amount,
                    e.expense_date,
                    e.description
                FROM expenses e
                LEFT JOIN categories c ON c.category_id = e.category_id
                WHERE e.user_id = :user_id
            ";

            $params = [":user_id" => $user_id];

            if($month){
                $sql .= " AND DATE_FORMAT(e.expense_date, '%Y-%m') = :month ";
                $params[":month"] = $month;
            }

            $sql .= " ORDER BY e.expense_date DESC, e.expense_id DESC ";

            $statement = $this->db->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_expense($user_id, $expense_id)
    {
        try{

            $sql = "
                SELECT expense_id, category_id, amount, expense_date, description
                FROM expenses
                WHERE user_id = :user_id
                AND expense_id = :expense_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"    => $user_id,
                ":expense_id" => $expense_id
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function create_expense($user_id, $category_id, $amount, $date, $description)
    {
        try{

            $sql = "
                INSERT INTO expenses
                    (user_id, category_id, amount, expense_date, description, created_at, updated_at)
                VALUES
                    (:user_id, :category_id, :amount, :expense_date, :description, NOW(), NOW())
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"      => $user_id,
                ":category_id"  => $category_id ?: null,
                ":amount"       => $amount,
                ":expense_date" => $date,
                ":description"  => $description ?: null
            ]);

            return (int) $this->db->lastInsertId();

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_expense($user_id, $expense_id, $category_id, $amount, $date, $description)
    {
        try{

            $sql = "
                UPDATE expenses
                SET
                    category_id  = :category_id,
                    amount       = :amount,
                    expense_date = :expense_date,
                    description  = :description,
                    updated_at   = NOW()
                WHERE user_id = :user_id
                AND expense_id = :expense_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":category_id"  => $category_id ?: null,
                ":amount"       => $amount,
                ":expense_date" => $date,
                ":description"  => $description ?: null,
                ":user_id"      => $user_id,
                ":expense_id"   => $expense_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function delete_expense($user_id, $expense_id)
    {
        try{

            $sql = "
                DELETE FROM expenses
                WHERE user_id = :user_id
                AND expense_id = :expense_id
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"    => $user_id,
                ":expense_id" => $expense_id
            ]);

            return $statement->rowCount() > 0;

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  INCOME
    // =====================================================
    // =====================================================

    public function get_income($user_id, $month = null)
    {
        try{

            $sql = "
                SELECT
                    i.income_id,
                    i.category_id,
                    c.name AS category_name,
                    i.source,
                    i.amount,
                    i.income_date,
                    i.description
                FROM income i
                LEFT JOIN categories c ON c.category_id = i.category_id
                WHERE i.user_id = :user_id
            ";

            $params = [":user_id" => $user_id];

            if($month){
                $sql .= " AND DATE_FORMAT(i.income_date, '%Y-%m') = :month ";
                $params[":month"] = $month;
            }

            $sql .= " ORDER BY i.income_date DESC, i.income_id DESC ";

            $statement = $this->db->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_income_row($user_id, $income_id)
    {
        try{

            $sql = "
                SELECT income_id, category_id, source, amount, income_date, description
                FROM income
                WHERE user_id = :user_id
                AND income_id = :income_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"   => $user_id,
                ":income_id" => $income_id
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function create_income($user_id, $category_id, $source, $amount, $date, $description)
    {
        try{

            $sql = "
                INSERT INTO income
                    (user_id, category_id, source, amount, income_date, description, created_at, updated_at)
                VALUES
                    (:user_id, :category_id, :source, :amount, :income_date, :description, NOW(), NOW())
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"     => $user_id,
                ":category_id" => $category_id ?: null,
                ":source"      => $source,
                ":amount"      => $amount,
                ":income_date" => $date,
                ":description" => $description ?: null
            ]);

            return (int) $this->db->lastInsertId();

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_income($user_id, $income_id, $category_id, $source, $amount, $date, $description)
    {
        try{

            $sql = "
                UPDATE income
                SET
                    category_id = :category_id,
                    source      = :source,
                    amount      = :amount,
                    income_date = :income_date,
                    description = :description,
                    updated_at  = NOW()
                WHERE user_id = :user_id
                AND income_id = :income_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":category_id" => $category_id ?: null,
                ":source"      => $source,
                ":amount"      => $amount,
                ":income_date" => $date,
                ":description" => $description ?: null,
                ":user_id"     => $user_id,
                ":income_id"   => $income_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function delete_income($user_id, $income_id)
    {
        try{

            $sql = "
                DELETE FROM income
                WHERE user_id = :user_id
                AND income_id = :income_id
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"   => $user_id,
                ":income_id" => $income_id
            ]);

            return $statement->rowCount() > 0;

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  INVESTMENTS
    // =====================================================
    // =====================================================

    public function get_investments($user_id)
    {
        try{

            $sql = "
                SELECT
                    i.investment_id,
                    i.category_id,
                    c.name AS category_name,
                    i.name,
                    i.amount_invested,
                    i.current_value,
                    i.gain_loss,
                    i.purchase_date,
                    i.notes
                FROM investments i
                LEFT JOIN categories c ON c.category_id = i.category_id
                WHERE i.user_id = :user_id
                ORDER BY i.purchase_date DESC, i.investment_id DESC
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_investment($user_id, $investment_id)
    {
        try{

            $sql = "
                SELECT investment_id, category_id, name, amount_invested, current_value, purchase_date, notes
                FROM investments
                WHERE user_id = :user_id
                AND investment_id = :investment_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"       => $user_id,
                ":investment_id" => $investment_id
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function create_investment($user_id, $category_id, $name, $invested, $current, $date, $notes)
    {
        try{

            $sql = "
                INSERT INTO investments
                    (user_id, category_id, name, amount_invested, current_value, purchase_date, notes, created_at, updated_at)
                VALUES
                    (:user_id, :category_id, :name, :invested, :current, :purchase_date, :notes, NOW(), NOW())
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"       => $user_id,
                ":category_id"   => $category_id ?: null,
                ":name"          => $name,
                ":invested"      => $invested,
                ":current"       => $current,
                ":purchase_date" => $date,
                ":notes"         => $notes ?: null
            ]);

            return (int) $this->db->lastInsertId();

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_investment($user_id, $investment_id, $category_id, $name, $invested, $current, $date, $notes)
    {
        try{

            $sql = "
                UPDATE investments
                SET
                    category_id     = :category_id,
                    name            = :name,
                    amount_invested = :invested,
                    current_value   = :current,
                    purchase_date   = :purchase_date,
                    notes           = :notes,
                    updated_at      = NOW()
                WHERE user_id = :user_id
                AND investment_id = :investment_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":category_id"     => $category_id ?: null,
                ":name"            => $name,
                ":invested"        => $invested,
                ":current"         => $current,
                ":purchase_date"   => $date,
                ":notes"           => $notes ?: null,
                ":user_id"         => $user_id,
                ":investment_id"   => $investment_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function delete_investment($user_id, $investment_id)
    {
        try{

            $sql = "
                DELETE FROM investments
                WHERE user_id = :user_id
                AND investment_id = :investment_id
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"       => $user_id,
                ":investment_id" => $investment_id
            ]);

            return $statement->rowCount() > 0;

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_investment_totals($user_id)
    {
        try{

            $sql = "
                SELECT
                    COALESCE(SUM(amount_invested), 0) AS total_invested,
                    COALESCE(SUM(current_value), 0)   AS total_current_value,
                    COALESCE(SUM(gain_loss), 0)        AS total_gain_loss
                FROM investments
                WHERE user_id = :user_id
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  BUDGETS
    // =====================================================
    // =====================================================

    public function get_budget_status($user_id, $month = null)
    {
        try{

            $sql = "
                SELECT budget_id, user_id, budget_month, category_name, budget_amount, spent, remaining
                FROM v_budget_status
                WHERE user_id = :user_id
            ";

            $params = [":user_id" => $user_id];

            if($month){
                $sql .= " AND budget_month = :month ";
                $params[":month"] = $month . "-01";
            }

            $sql .= " ORDER BY budget_month DESC, category_name ASC ";

            $statement = $this->db->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_budget($user_id, $budget_id)
    {
        try{

            $sql = "
                SELECT budget_id, user_id, category_id, budget_month, amount
                FROM budgets
                WHERE user_id = :user_id
                AND budget_id = :budget_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"   => $user_id,
                ":budget_id" => $budget_id
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function upsert_budget($user_id, $category_id, $month, $amount)
    {
        try{

            $sql = "
                INSERT INTO budgets (user_id, category_id, budget_month, amount, created_at, updated_at)
                VALUES (:user_id, :category_id, :budget_month, :amount, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    amount = VALUES(amount),
                    updated_at = NOW()
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":user_id"      => $user_id,
                ":category_id"  => $category_id,
                ":budget_month" => $month . "-01",
                ":amount"       => $amount
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_budget_amount($user_id, $budget_id, $amount)
    {
        try{

            $sql = "
                UPDATE budgets
                SET amount = :amount, updated_at = NOW()
                WHERE user_id = :user_id
                AND budget_id = :budget_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":amount"    => $amount,
                ":user_id"   => $user_id,
                ":budget_id" => $budget_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function delete_budget($user_id, $budget_id)
    {
        try{

            $sql = "
                DELETE FROM budgets
                WHERE user_id = :user_id
                AND budget_id = :budget_id
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id"   => $user_id,
                ":budget_id" => $budget_id
            ]);

            return $statement->rowCount() > 0;

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  TRANSACTIONS (read-only, union view)
    // =====================================================
    // =====================================================

    public function get_transactions($user_id)
    {
        try{

            $sql = "
                SELECT user_id, txn_type, txn_id, txn_date, description, category_name, amount
                FROM v_transactions
                WHERE user_id = :user_id
                ORDER BY txn_date DESC, txn_id DESC
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }



    // =====================================================
    // =====================================================
    //  DASHBOARD / REPORTS
    // =====================================================
    // =====================================================

    public function get_monthly_summary($user_id, $month = null)
    {
        try{

            $sql = "
                SELECT user_id, month_start, total_income, total_expenses, savings
                FROM v_monthly_summary
                WHERE user_id = :user_id
            ";

            $params = [":user_id" => $user_id];

            if($month){
                $sql .= " AND month_start = :month ";
                $params[":month"] = $month . "-01";
            }else{
                $sql .= " ORDER BY month_start DESC ";
            }

            $statement = $this->db->prepare($sql);
            $statement->execute($params);

            if($month){
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return $row ?: [
                    "user_id"        => $user_id,
                    "month_start"    => $month . "-01",
                    "total_income"   => 0,
                    "total_expenses" => 0,
                    "savings"        => 0
                ];
            }

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_monthly_income_expense_chart($user_id)
{
    try{

        $sql = "
            SELECT
                month_start,
                total_income,
                total_expenses
            FROM v_monthly_summary
            WHERE user_id = :user_id
            ORDER BY month_start ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            ":user_id" => $user_id
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);

    }catch(\Throwable $th){
        throw $th;
    }
}



    public function get_grand_totals($user_id)
    {
        try{

            $sql = "
                SELECT
                    COALESCE(SUM(total_income), 0)   AS grand_income,
                    COALESCE(SUM(total_expenses), 0) AS grand_expenses,
                    COALESCE(SUM(savings), 0)        AS grand_savings,
                    MIN(month_start) AS first_month,
                    MAX(month_start) AS last_month,
                    COUNT(*) AS months_tracked
                FROM v_monthly_summary
                WHERE user_id = :user_id
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_available_months($user_id)
    {
        try{

            $sql = "
                SELECT DISTINCT DATE_FORMAT(txn_date, '%Y-%m') AS ym
                FROM v_transactions
                WHERE user_id = :user_id
                ORDER BY ym DESC
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetchAll(PDO::FETCH_COLUMN);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_category_breakdown($user_id, $month)
    {
        try{

            $sql = "
                SELECT COALESCE(c.name, 'Uncategorized') AS name, SUM(e.amount) AS total
                FROM expenses e
                LEFT JOIN categories c ON c.category_id = e.category_id
                WHERE e.user_id = :user_id
                AND DATE_FORMAT(e.expense_date, '%Y-%m') = :month
                GROUP BY COALESCE(c.name, 'Uncategorized')
                ORDER BY total DESC
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":user_id" => $user_id,
                ":month"   => $month
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_recent_transactions($user_id, $limit = 6)
    {
        try{

            $limit = (int) $limit;

            $sql = "
                SELECT txn_type, txn_id, txn_date, description, category_name, amount
                FROM v_transactions
                WHERE user_id = :user_id
                ORDER BY txn_date DESC, txn_id DESC
                LIMIT {$limit}
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


   




    // =====================================================
    // =====================================================
    //  USER / SETTINGS
    // =====================================================
    // =====================================================

    public function get_user_by_id($user_id)
    {
        try{

            $sql = "
                SELECT user_id, full_name, email, phone, is_active, last_login_at, created_at
                FROM users
                WHERE user_id = :user_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function email_taken_by_other($email, $user_id)
    {
        try{

            $sql = "
                SELECT user_id
                FROM users
                WHERE email = :email
                AND user_id != :user_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":email"   => $email,
                ":user_id" => $user_id
            ]);

            return (bool) $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_profile($user_id, $name, $email, $phone)
    {
        try{

            $sql = "
                UPDATE users
                SET full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":full_name" => $name,
                ":email"     => $email,
                ":phone"     => $phone,
                ":user_id"   => $user_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function get_password_hash($user_id)
    {
        try{

            $sql = "
                SELECT password_hash
                FROM users
                WHERE user_id = :user_id
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);
            $statement->execute([":user_id" => $user_id]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row ? $row['password_hash'] : null;

        }catch(\Throwable $th){
            throw $th;
        }
    }


    public function update_password($user_id, $passwordHash)
    {
        try{

            $sql = "
                UPDATE users
                SET password_hash = :password_hash, updated_at = NOW()
                WHERE user_id = :user_id
            ";

            $statement = $this->db->prepare($sql);

            return $statement->execute([
                ":password_hash" => $passwordHash,
                ":user_id"       => $user_id
            ]);

        }catch(\Throwable $th){
            throw $th;
        }
    }

}






?>