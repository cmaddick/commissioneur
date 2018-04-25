<?php

use Slim\Http\UploadedFile;

class Submission
{
    private $pdo;
    private $id = 0;
    private $title = '';
    private $path = '';
    private $description = '';

    public function __construct($id, $title, $path, $description, $pdo) {
        $this->pdo = $pdo;

        $this->id = $id;
        $this->title = $title;
        $this->path = $path;
        $this->description = $description;
    }

    public static function get_submission($submissionID, $pdo) {
        // Static factory method for creating submission objects

        $stmt = $pdo->prepare('SELECT * FROM submissions WHERE SubmissionID = ?');
        $stmt->execute([$submissionID]);
        $row = $stmt->fetch();

        if ($row) {
            $newSubmission = new Submission($submissionID, $row['ContentTitle'], $row['ContentPath'], $row['ContentDescription'], $pdo);

            return $newSubmission;
        } else {
            return null;
        }
    }

    public static function save_new_submission($pdo, $title, $type, $description, $file, $uploadDir) {
        // Prepares file upload and adds new submission entry to database

        $submissionID = mt_rand(100000000, 999999999);
        $dbPath = self::set_dbPath($type, $uploadDir);
        $uploadDir = self::set_upload_subdir($type, $uploadDir);

        if (!self::get_submission($submissionID, $pdo)) {
            // Handle file upload
            if ($file->getError() === UPLOAD_ERR_OK) {
                $filename = self::moveUploadedFile($uploadDir, $file, $submissionID);

                // Get relative path to submission
                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $dbPath = $dbPath . "/" . $submissionID . "." . $extension;

                // Push to database
                $stmt = $pdo->prepare('INSERT INTO `submissions` (`SubmissionID`, `SubmissionOwner`, `ContentType`, `ContentPath`, `ContentTitle`, `ContentDescription`) VALUES (:submissionid, :submissionOwner, :contentType, :contentPath, :contentTitle, :contentDescription)');
                $stmt->bindParam(':submissionid', $submissionID);
                $stmt->bindParam(':submissionOwner', $_SESSION['UserID']);
                $stmt->bindParam(':contentType', $type);
                $stmt->bindParam(':contentPath', $dbPath);
                $stmt->bindParam(':contentTitle', $title);
                $stmt->bindParam(':contentDescription', $description);
                $stmt->execute();

                return self::get_submission($submissionID, $pdo);
            } else {
                return null;
            }
        }
    }

    private static function set_dbPath ($type, $uploadDir) {
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
            'title' => $this->title,
            'path' => $this->path,
            'description' => $this->description
        ];

        return $submissionArray;
    }
}