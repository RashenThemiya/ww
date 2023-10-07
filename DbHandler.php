class DbHandler
{
    private $servername = "localhost";
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