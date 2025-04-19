<?php
include 'main.php';
// Default account values
$acc = [
    'email' => '',
    'password' => '',
    'full_name' => '',
    'role' => 'Member',
    'secret' => '',
    'last_seen' => date('Y-m-d\TH:i'),
    'status' => 'Idle',
    'departments' => '',
    'photo_url' => '',
    'ip' => '',
    'user_agent' => '',
    'registered' => date('Y-m-d\TH:i')
];
// Retrieve all departments from the database
$departments = $pdo->query('SELECT * FROM departments')->fetchAll();
$acc_departments = [];
// Retrieve the GET ID param (if specified)
if (isset($_GET['id'])) {
    // Retrieve the account from the database
    if ($_SESSION['chat_account_role'] == 'Admin') {
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ? AND role != "Admin"');
    }
    $stmt->execute([ $_GET['id'] ]);
    $acc = $stmt->fetch(PDO::FETCH_ASSOC);
    // Retrieve all departments for the account
    $stmt = $pdo->prepare('SELECT * FROM departments WHERE FIND_IN_SET(id, ?)');
    $stmt->execute([ $acc['departments'] ]);
    $acc_departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing account
    $page = 'Edit';
    if (isset($_POST['submit']) && $acc) {
        // Update the account
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $account['password'];
        if ($_SESSION['chat_account_role'] == 'Admin') {
            // convert departments to comma separated string
            $departments = isset($_POST['departments']) ? implode(',', $_POST['departments']) : '';
            $stmt = $pdo->prepare('UPDATE accounts SET email = ?, password = ?, role = ?, full_name = ?, secret = ?, last_seen = ?, status = ?, departments = ?, photo_url = ?, ip = ?, user_agent = ?, registered = ? WHERE id = ?');
            $stmt->execute([ $_POST['email'], $password, $_POST['role'], $_POST['full_name'], $_POST['secret'], $_POST['last_seen'], $_POST['status'], $departments, $_POST['photo_url'], $_POST['ip'], $_POST['user_agent'], $_POST['registered'], $_GET['id'] ]);
        } else {
            $stmt = $pdo->prepare('UPDATE accounts SET email = ?, password = ?, full_name = ?, secret = ?, last_seen = ?, status = ?, photo_url = ? WHERE id = ?');
            $stmt->execute([ $_POST['email'], $password, $_POST['full_name'], $_POST['secret'], $_POST['last_seen'], $_POST['status'], $_POST['photo_url'], $_GET['id'] ]);            
        }
        header('Location: accounts.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete']) && $_SESSION['chat_account_role'] == 'Admin') {
        // Delete the account
        $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: accounts.php?success_msg=3');
        exit;
    }
    if (!$acc) {
        exit('Invalid request!');
    }
} else if ($_SESSION['chat_account_role'] == 'Admin') {
    // Create a new account
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
        // convert departments to comma separated string
        $departments = isset($_POST['departments']) ? implode(',', $_POST['departments']) : '';
        $stmt = $pdo->prepare('INSERT INTO accounts (email,password,role,full_name,secret,last_seen,status,departments,photo_url,ip,user_agent,registered) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['email'], $password, $_POST['role'], $_POST['full_name'], $_POST['secret'], $_POST['last_seen'], $_POST['status'], $departments, $_POST['photo_url'], $_POST['ip'], $_POST['user_agent'], $_POST['registered'] ]);
        header('Location: accounts.php?success_msg=1');
        exit;
    }
} else {
    exit('Invalid request!');
}
?>
<?=template_admin_header($page . ' Account', 'accounts', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Account</h2>
        <a href="accounts.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit' && $_SESSION['chat_account_role'] == 'Admin'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this account?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="email"><i class="required">*</i> Email</label>
            <input id="email" type="email" name="email" placeholder="Email" value="<?=htmlspecialchars($acc['email'], ENT_QUOTES)?>" required>

            <label for="password"><?=$page == 'Edit' ? 'New ' : ''?>Password</label>
            <input type="text" id="password" name="password" placeholder="<?=$page == 'Edit' ? 'New ' : ''?>Password">

            <label for="full_name">Full Name</label>
            <input id="full_name" type="text" name="full_name" placeholder="Joe Bloggs" value="<?=htmlspecialchars($acc['full_name'], ENT_QUOTES)?>">

            <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="Guest"<?=$acc['role']=='Guest'?' selected':''?>>Guest</option>
                <option value="Operator"<?=$acc['role']=='Operator'?' selected':''?>>Operator</option>
                <option value="Admin"<?=$acc['role']=='Admin'?' selected':''?>>Admin</option>
            </select>
            <?php endif; ?>

            <label for="secret">Secret Authentication Cookie</label>
            <input id="secret" type="text" name="secret" placeholder="Secret Authentication Cookie" value="<?=htmlspecialchars($acc['secret'], ENT_QUOTES)?>">

            <label for="last_seen">Last Seen Date</label>
            <input id="last_seen" type="datetime-local" name="last_seen" value="<?=date('Y-m-d\TH:i', strtotime($acc['last_seen']))?>" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Idle"<?=$acc['status']=='Idle'?' selected':''?>>Idle</option>
                <option value="Occupied"<?=$acc['status']=='Occupied'?' selected':''?>>Occupied</option>
                <option value="Waiting"<?=$acc['status']=='Waiting'?' selected':''?>>Waiting</option>
                <option value="Away"<?=$acc['status']=='Away'?' selected':''?>>Away</option>
            </select>

            <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
            <label for="departments">Departments</label>
            <div class="multiselect" data-name="departments[]">
                <?php foreach ($acc_departments as $dep): ?>
                <span class="item" data-value="<?=$dep['id']?>">
                    <i class="remove">&times;</i><?=$dep['title']?>
                    <input type="hidden" name="departments[]" value="<?=$dep['id']?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="departments" placeholder="Departments">
                <div class="list">
                    <?php foreach ($departments as $dep): ?>
                    <span data-value="<?=$dep['id']?>"><?=$dep['title']?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <label for="photo_url">Photo URL</label>
            <input id="photo_url" type="text" name="photo_url" placeholder="Photo URL" value="<?=htmlspecialchars($acc['photo_url'], ENT_QUOTES)?>">

            <?php if ($_SESSION['chat_account_role'] == 'Admin'): ?>
            <label for="ip">IP Address</label>
            <input id="ip" type="text" name="ip" placeholder="IP Address" value="<?=htmlspecialchars($acc['ip'], ENT_QUOTES)?>">

            <label for="user_agent">User Agent</label>
            <input id="user_agent" type="text" name="user_agent" placeholder="User Agent" value="<?=htmlspecialchars($acc['user_agent'], ENT_QUOTES)?>">

            <label for="registered">Registered Date</label>
            <input id="registered" type="datetime-local" name="registered" value="<?=date('Y-m-d\TH:i', strtotime($acc['registered']))?>" required>
            <?php endif; ?>

        </div>

    </div>

</form>

<?=template_admin_footer()?>