<?php
date_default_timezone_set('Asia/Dhaka');
$show_frontend = false;

function db_connect(){
    $host = 'localhost';
    $dbname = 'dipchakr_expanse_manage';
    $username = 'dipchakr_expanse_manage';
    $password = '0hX%AHwYw%!m';
    try{
        $dsn = 'mysql:host='.$host.'; dbname='.$dbname;
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
    catch (PDOException $e) {
        //echo $e->getMessage();
        return null;
    }   
}

if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["query"]) && !empty($_GET["query"])) {
        if($_GET["query"] == 'topics'){
            $pdo = db_connect();
            $sql = "SELECT * FROM `topics`";
            $stmt = $pdo->prepare($sql);
            try{
                $stmt->execute();
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($res);
                exit;
            }
            catch (PDOException $e){
                echo $e->getMessage();
                exit;
            }
        }
    }
    $show_frontend = true;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if ((isset($_POST["expenseTopic"]) && !empty($_POST["expenseTopic"])) && 
    (isset($_POST["expenseDetails"]) && !empty($_POST["expenseDetails"])) && 
    (isset($_POST["expenseValue"]) && !empty($_POST["expenseValue"])) &&
    (isset($_POST["expenseDateTime"]) && !empty($_POST["expenseDateTime"])))
     {
        $expenseTopic = $_POST["expenseTopic"];
        $pdo = db_connect();
        $sql = "SELECT * FROM `topics`";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unique_topic = true;
        foreach ($res as $r){
            if(strtolower($r['name']) == strtolower($expenseTopic)){
                $unique_topic = false; 
                break;
            }
        }
        if($unique_topic){
            $sql = "INSERT INTO topics (name, total) VALUES ('$expenseTopic', NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        $expenseDetails = $_POST["expenseDetails"];
        $expenseValue = $_POST["expenseValue"];
        $expenseDateTime = $_POST["expenseDateTime"];
        if($expenseDateTime){
            $date_exploder = explode("T",$expenseDateTime);
            $amount_date = $date_exploder[0];
            $new_time = str_replace("T", " ", $expenseDateTime);
        }
        else{
            $current_timestamp = time();
            $new_time = date('Y-m-d H:i:s', $current_timestamp);
            $amount_date = date('Y-m-d', $current_timestamp);
        }
        $pdo = db_connect();
        try{
            $sql = "INSERT INTO expenses (topic, details, amount, date_time) VALUES ('$expenseTopic', '$expenseDetails', '$expenseValue', '$new_time')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $sql = "SELECT SUM(amount) as total_amount FROM `expenses` WHERE DATE(`date_time`) = '$amount_date' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_amount = $res['total_amount'];
            echo "Expense added successfully\n\nAmount Expense on $amount_date is $total_amount so far";
        }
        catch (PDOException $e){
            echo "Error: " . $e->getMessage();
        }
    }
    else{
        echo "Value required";
    }
}
?>
<?php if($show_frontend): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
    }
    .container {
        max-width: 400px;
        margin: 50px auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center; /* Center aligning content */
    }
    h2 {
        margin-top: 0;
    }
    .form-group {
        margin-bottom: 20px;
        margin-right: 20px;
        margin-left: 20px;
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .btn {
        display: inline-block;
        padding: 10px;
        font-size: 16px;
        color: #fff;
        background-color: #007bff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .btn:hover {
        background-color: #0056b3;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Expense</h2>
        <form id="expenseForm" action="./" method="post">
            <div class="form-group">
                <input type="text" id="expenseTopicInput" class="form-control" name="expenseTopic" placeholder="Enter Topic" required>
                <ul id="expenseTopicDropdown" class="dropdown-content"></ul>
            </div>
            <div class="form-group">
                <input type="text" id="expenseDetails" class="form-control" name="expenseDetails" placeholder="Enter Details" required>
            </div>
            <div class="form-group">
                <input type="number" id="expenseValue" class="form-control" name="expenseValue" placeholder="Enter Value" required>
            </div>
            <div class="form-group">
            <input type="datetime-local" step=1 id="expenseDateTime" class="form-control" name="expenseDateTime">
            </div>

            <button type="submit" id="addExpenseBtn" class="btn">Submit</button>
        </form>
    </div>
</body>
</html>
<?php endif;?>
<script>
fetch('https://dipchakraborty.com/expanse_manager/?query=topics')
  .then(response => response.json())
  .then(data => {
    const expenseTopics = data.map(item => item.name);
    const expenseTopicInput = document.getElementById("expenseTopicInput");
    const expenseTopicDropdown = document.getElementById("expenseTopicDropdown");
    function filterExpenseTopics() {
        const userInput = expenseTopicInput.value.toLowerCase();
        const filteredTopics = expenseTopics.filter(topic => topic.toLowerCase().startsWith(userInput) && topic.trim() !== '');
        expenseTopicDropdown.innerHTML = "";
        filteredTopics.forEach(topic => {
            const div = document.createElement("div");
            div.textContent = topic;
            div.addEventListener("click", () => {
                expenseTopicInput.value = topic;
                expenseTopicDropdown.innerHTML = "";
            });
            expenseTopicDropdown.appendChild(div);
        });
        if (userInput && filteredTopics.length > 0) {
            expenseTopicDropdown.style.display = "block";
        } else {
            expenseTopicDropdown.style.display = "none";
        }
    }
    expenseTopicInput.addEventListener("input", filterExpenseTopics);
    document.addEventListener("click", (event) => {
        if (!event.target.matches("#expenseTopicInput")) {
            expenseTopicDropdown.innerHTML = "";
        }
    });
  })
  .catch(error => console.error('Error:', error));

function updateDateTimeInput() {
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const month = (currentDate.getMonth() + 1).toString().padStart(2, '0'); 
    const day = currentDate.getDate().toString().padStart(2, '0');
    const hours = currentDate.getHours().toString().padStart(2, '0');
    const minutes = currentDate.getMinutes().toString().padStart(2, '0');
    const seconds = currentDate.getSeconds().toString().padStart(2, '0');
    const datetimeString = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    document.getElementById('expenseDateTime').value = datetimeString;
}
updateDateTimeInput();

const intervalId = setInterval(updateDateTimeInput, 100);
document.getElementById('expenseDateTime').addEventListener('click', function(event) {
    clearInterval(intervalId);
    console.log(intervalId);
});


document.getElementById("expenseForm").addEventListener("submit", function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    const notificationContainer = document.createElement("div");
    notificationContainer.classList.add("notification");

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("addExpenseBtn").disabled = false;
        document.getElementById("expenseTopicInput").value = "";
        document.getElementById("expenseDetails").value = "";
        document.getElementById("expenseValue").value = "";
        const responseMessage = data.split('<script>')[0].trim();
        notificationContainer.textContent = responseMessage;
        notificationContainer.style.color = "green";
        document.getElementById("expenseForm").insertBefore(notificationContainer, document.getElementById("addExpenseBtn"));
        // setTimeout(() => {
        //     notificationContainer.remove();
        // }, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        notificationContainer.textContent = "Error occurred while submitting the form.";
        notificationContainer.style.color = "red";
        document.getElementById("expenseForm").insertBefore(notificationContainer, document.getElementById("addExpenseBtn"));
        setTimeout(() => {
            notificationContainer.remove();
        }, 2000);
    });
});

</script>
