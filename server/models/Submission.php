<?php

use Slim\Http\UploadedFile;

class Submission
{
    private $pdo;
    private $ownerID = 0;
    private $id = 0;
    private $title = '';
    private $path = '';
    private $description = '';
    private $datetimeUploaded = '';

    public function __construct($id, $ownerID, $title, $path, $description, $datetimeUploaded, $pdo) {
        $this->pdo = $pdo;

        $this->id = $id;
        $this->ownerID = $ownerID;
        $this->title = $title;
        $this->path = $path;
        $this->description = $description;
        $this->datetimeUploaded = $datetimeUploaded;
    }

    public static function get_submission($submissionID, PDO $pdo) {
        // Static factory method for creating submission objects

        $stmt = $pdo->prepare('SELECT * FROM submissions WHERE SubmissionID = ?');
        $stmt->execute([$submissionID]);
        $row = $stmt->fetch();

        if ($row) {
            $newSubmission = new Submission($submissionID, $row['SubmissionOwner'], $row['ContentTitle'], $row['ContentPath'], $row['ContentDescription'], $row['DatetimeUploaded'], $pdo);

            return $newSubmission;
        } else {
            return null;
        }
    }

    public static function get_recent_submissions(PDO $pdo, $limit = 21) {
        $stmt = $pdo->prepare('SELECT * FROM submissions ORDER BY DatetimeUploaded DESC LIMIT ' . $limit);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $submissions = [];

        if ($rows) {
            foreach ($rows as $row) {
                $newSubmission = new Submission($row['SubmissionID'], $row['SubmissionOwner'], $row['ContentTitle'], $row['ContentPath'], $row['ContentDescription'], $row['DatetimeUploaded'], $pdo);
                $newSubmission = $newSubmission->get_submission_array();
                array_push($submissions, $newSubmission);
            }
            return $submissions;
        } else {
            return null;
        }
    }

    public static function save_new_submission(PDO $pdo, $title, $type, $description, $file, $uploadDir) {
        // Prepares file upload and adds new submission entry to database

        $submissionID = mt_rand(100000000, 999999999);
        $dbPath = self::set_dbPath($type, $uploadDir);
        $uploadDir = self::set_upload_subdir($type, $uploadDir);
        $uploadedDatetime = date('Y-m-d H:i:s T');

        if (!self::get_submission($submissionID, $pdo)) {
            // Handle file upload
            if ($file->getError() === UPLOAD_ERR_OK) {
                $filename = self::moveUploadedFile($uploadDir, $file, $submissionID);

                // Get relative path to submission
                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $dbPath = $dbPath . "/" . $submissionID . "." . $extension;

                // Push to database
                $stmt = $pdo->prepare('INSERT INTO `submissions` (`SubmissionID`, `SubmissionOwner`, `ContentType`, `ContentPath`, `ContentTitle`, `ContentDescription`, `DatetimeUploaded`) VALUES (:submissionid, :submissionOwner, :contentType, :contentPath, :contentTitle, :contentDescription, :datetimeUploaded)');
                $stmt->bindParam(':submissionid', $submissionID);
                $stmt->bindParam(':submissionOwner', $_SESSION['UserID']);
                $stmt->bindParam(':contentType', $type);
                $stmt->bindParam(':contentPath', $dbPath);
                $stmt->bindParam(':contentTitle', $title);
                $stmt->bindParam(':contentDescription', $description);
                $stmt->bindParam(':datetimeUploaded', $uploadedDatetime);
                $stmt->execute();

                return self::get_submission($submissionID, $pdo);
            } else {
                return null;
            }
        }
    }

    public function getID(){
        return $this->id;
    }

    private static function set_dbPath ($type) {
        // Sets the db relative path dependent on upload type

        if ($type === "image") {
            $dbPath = "../public/resources/usercontent/images";
            return $dbPath;
        } elseif ($type === "audio") {
            $dbPath = "../public/resources/usercontent/audio";
            return $dbPath;
        } else {
            return null;
        }
    }

    private static function set_upload_subdir($type, $uploadDir)  {
        // Appends the appropriate subdirectory to uploadDir for upload type

        if ($type === "image") {
            $uploadDirectory = $uploadDir . '/images';
            return $uploadDirectory;
        } elseif ($type === "audio") {
            $uploadDirectory = $uploadDir . '/audio';
            return $uploadDirectory;
        } else {
            return null;
        }
    }

    private static function moveUploadedFile($directory, UploadedFile $uploadedFile, $id) {
        // Moves uploaded file from temporary upload directory to media directory

        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%s', $id, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    public function get_submission_array() {
        // Returns array for use with twig template

        $submissionArray = [];

        $submissionArray = [
            'id' => $this->id,
            'ownerID' => $this->ownerID,
            'title' => $this->title,
            'path' => $this->path,
            'description' => $this->description,
            'datetimeUploaded' => $this->datetimeUploaded
        ];

        return $submissionArray;
    }
}