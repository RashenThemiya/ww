<?php
// Include the database connection class
$authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

class DbHandler
{
    private $servername = "175.157.225.64";
    private $username = "rasthe_user";
    private $password = "2Vishwajith@";
    private $dbname = "rasthe_user";
    private $conn;

    public function __construct()
    {
        $this->conn = $this->getConnstring();
    }

    private function getConnstring()
    {
        $conn = mysqli_connect($this->servername, $this->username, $this->password, $this->dbname);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        return $conn;
    }

    public function saveOTP($phoneNumber, $otp)
    {
        $sql = "INSERT INTO otp_verification (phone_number, otp) VALUES ('$phoneNumber', '$otp')";
        if (mysqli_query($this->conn, $sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function getSavedOTP($phoneNumber)
    {
        $sql = "SELECT otp FROM otp_verification WHERE phone_number = '$phoneNumber'";
        $result = mysqli_query($this->conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['otp'];
        } else {
            return false;
        }
    }

    public function clearOTP($phoneNumber)
    {
        $sql = "DELETE FROM otp_verification WHERE phone_number = '$phoneNumber'";
        if (mysqli_query($this->conn, $sql)) {
            return true;
        } else {
            return false;
        }
    }
}


// Initialize the DbHandler
$dbHandler = new DbHandler();

// API endpoint for generating and sending OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the phone number from the POST request
    $phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;

    if ($phoneNumber) {
        // Generate a random OTP (You can implement your own OTP generation logic)
        $otp = rand(1000, 9999);

        // Save the OTP to the database
        if ($dbHandler->saveOTP($phoneNumber, $otp)) {
            // Send the OTP via SMS using your desired method and API
            $smsResponse = sendSMS($phoneNumber, $otp);

            // Handle the SMS response here (You need to implement sendSMS function)
            if ($smsResponse['statusCode'] === 'S1000') {
                // SMS sent successfully
                echo json_encode(['message' => 'OTP sent successfully']);
            } else {
                echo json_encode(['message' => 'Failed to send OTP via SMS']);
            }
        } else {
            echo json_encode(['message' => 'Failed to save OTP']);
        }
    } else {
        echo json_encode(['message' => 'Phone number is missing in the request']);
    }
}

// API endpoint for OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the phone number and OTP from the query parameters
    $phoneNumber = isset($_GET['phone_number']) ? $_GET['phone_number'] : null;
    $userOTP = isset($_GET['otp']) ? $_GET['otp'] : null;

    if ($phoneNumber && $userOTP) {
        // Retrieve the saved OTP from the database
        $savedOTP = $dbHandler->getSavedOTP($phoneNumber);

        if ($savedOTP !== false && $userOTP === $savedOTP) {
            // OTP is valid
            $dbHandler->clearOTP($phoneNumber); // Clear the OTP from the database
            echo json_encode(['message' => 'OTP verification successful']);
        } else {
            // OTP is invalid
            echo json_encode(['message' => 'OTP verification failed']);
        }
    } else {
        echo json_encode(['message' => 'Phone number or OTP is missing in the request']);
    }
}

function sendSMS($phoneNumber, $otp) {
    // Define the SMS API endpoint
    $smsApiUrl = 'http://core.sol:65382/smsmo';

    // Define the SMS request data as JSON
    $requestData = [
        'message' => 'OTP',
        'destinationAddresses' => ["tel:$phoneNumber"],
        'password' => 'password',
        'applicationId' => 'APP_999999',
    ];

    // Encode the request data as JSON
    $jsonData = json_encode($requestData);

    // Initialize cURL session
    $ch = curl_init($smsApiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData),
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        return ['statusCode' => 'E500', 'statusDetail' => 'cURL Error: ' . curl_error($ch)];
    }

    // Close the cURL session
    curl_close($ch);

    // Parse the JSON response
    $responseData = json_decode($response, true);

    // Check if the response was successful
    if (isset($responseData['statusCode']) && $responseData['statusCode'] === 'S1000') {
        // SMS sent successfully
        return ['statusCode' => 'S1000', 'statusDetail' => 'Success'];
    } else {
        // SMS sending failed
        return ['statusCode' => 'E500', 'statusDetail' => 'SMS sending failed'];
    }
}

?>