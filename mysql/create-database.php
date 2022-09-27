<?php

$defaults = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'root_user' => 'root',
    'root_password' => 'root',
    'name' => '',
    'user' => '',
    'password' => '',
    'charset' => 'utf8',
    'collate' => 'utf8_general_ci',
];
$errors = [];
$success = [];

$inputVars = filter_input_array(INPUT_POST);

if (is_array($inputVars)) {
    $vars = array_intersect_key($inputVars, $defaults);
    if (array_key_exists('create', $inputVars)) {
        try {
            // validate host
            //TODO: validations

            $pdo = new PDO('mysql:host='.$vars['host'].':'.$vars['port'], $vars['root_user'], $vars['root_password']);

            // Create database
            $database = $vars['name'];
            if (!preg_match('~[0-9a-zA-Z$_]+~', $database)) {
                throw new Exception('Wrong database name');
            }
            $sql = "create database if not exists `$database`;";
            if ($pdo->exec($sql)) {
                $success['success'] = [
                    'message' => 'Database created successfully',
                ];

                // Create user
                $sql = "create user if not exists :user@'localhost' identified with mysql_native_password by :password;";
                $statement = $pdo->prepare($sql);
                $statement->bindValue(':user', $vars['user'], PDO::PARAM_STR);
                $statement->bindValue(':password', $vars['password'], PDO::PARAM_STR);
                $result = $statement->execute();

                if ($result) {
                    $success['success'] = [
                        'message' => 'User created successfully',
                    ];

                    // Grant privileges
                    $sql = "grant all privileges on `$database`.* to :user@'localhost';";
                    $statement = $pdo->prepare($sql);
                    $statement->bindValue(':user', $vars['user'], PDO::PARAM_STR);
                    $result = $statement->execute();

                    if ($result) {
                        $success['success'] = [
                            'message' => 'Privileges granted',
                        ];

                        $sql = 'flush privileges;';
                        $pdo->exec($sql);

                        $success['success'] = [
                            'message' => 'Database created successfully',
                        ];
                    } else {
                        throw new Exception('Could not grant privileges');
                    }
                } else {
                    throw new Exception('Could not create user');
                }

            } else {
                throw new Exception('Could not create database.');
            }
        } catch (PDOException $exception) {
            $errors['connection'] = [
                    'message' => 'Database error.',
                    'details' => $exception->getMessage(),
            ];
        } catch (Exception $exception) {
            $errors['connection'] = [
                'message' => $exception->getMessage(),
            ];
        }
    }
} else {
    $vars = $defaults;
}

extract($vars);

?>
<html>
<head>

</head>
<body>
<?php if (!empty($success)) { ?>
    <div style="border: green 1px solid; background-color: #b0ff9e; padding: 11px;">
        <?php foreach ($success as $type => $message) { ?>
            <div>
                <span><?php echo $message['message']; ?></span>
                <?php if (array_key_exists('details', $message)) { ?>
                    <hr/><span style="font-style: italic;"><?php echo $message['details']; ?></span>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
<?php } ?>
<?php if (!empty($errors)) { ?>
<div style="border: red 1px solid; background-color: #ffb99e; padding: 11px;">
    <?php foreach ($errors as $type => $error) { ?>
    <div>
        <span><?php echo $error['message']; ?></span>
        <?php if (array_key_exists('details', $error)) { ?>
            <hr/><span style="font-style: italic;"><?php echo $error['details']; ?></span>
        <?php } ?>
    </div>
    <?php } ?>
</div>
<?php } ?>
<form method="post">
    <div style="margin-top: 11px;">
        <label>
            <span>Host: </span>
            <input type="text" name="host" value="<?php echo $host; ?>">
            <span>Port: </span>
            <input type="text" name="port" value="<?php echo $port; ?>">
        </label>
    </div>
    <div style="margin-top: 11px;">
        <label>
            <span>Root user: </span>
            <input type="text" name="root_user" value="<?php echo $root_user;?>">
            <span>Password: </span>
            <input type="password" name="root_password" value="<?php echo $root_password?>">
        </label>
    </div>
    <div style="margin-top: 11px;">
        <label>
            <span>Character set: </span>
            <input type="text" name="charset" value="<?php echo $charset;?>">
            <span>Collate: </span>
            <input type="text" name="collate" value="<?php echo $collate?>">
        </label>
    </div>
    <div style="margin-top: 11px;">
        <label>
            <span>Database name: </span>
            <input type="text" name="name" value="<?php echo $name;?>">
            <span>User: </span>
            <input type="text" name="user" value="<?php echo $user?>">
            <span>Password: </span>
            <input type="password" name="password" value="<?php echo $password?>">
        </label>
    </div>
    <div style="margin-top: 11px;">
        <input type="submit" value="Create" name="create">
    </div>
</form>
</body>
</html>
