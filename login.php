<php?


$database ='torn'

$user='root'
$password=''
host='localhost'
$conn = new mysqli($host, $user, $password, $database);

$conn->query("SELECT * FROM users WHERE username = 'admin'")->fetch_assoc();
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === $admin['username'] && $password === $admin['password']) {
        echo "Login successful!";
}