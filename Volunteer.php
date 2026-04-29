<?php
require_once 'User.php';

class Volunteer extends User {
    
    // Use Case: communal task <<include>> assign points
    public function joinShift($shiftId) {
        $stmt = $this->db->prepare("INSERT INTO shift_volunteers (shiftId, volunteerId) VALUES (?, ?)");
        $result = $stmt->execute([$shiftId, $this->userId]);
        
        if($result) {
            $this->db->prepare("UPDATE shifts SET currentVolunteers = currentVolunteers + 1 WHERE shiftId = ?")->execute([$shiftId]);
            $this->addServiceHours(2); 
        }
        return $result;
    }

    private function addServiceHours($hours) {
        $stmt = $this->db->prepare("UPDATE volunteers SET Servicehours = Servicehours + ? WHERE volunteerId = ?");
        $stmt->execute([$hours, $this->userId]);
        $this->addPoints($hours * 10); 
    }

    //Use Case: shift substitution <<extend>> calculate delay
    public function swapShift($myShiftId, $otherVolunteerId) {
        //delay calculation
        $stmt = $this->db->prepare("UPDATE shift_volunteers SET volunteerId = ? WHERE shiftId = ? AND volunteerId = ?");
        return $stmt->execute([$otherVolunteerId, $myShiftId, $this->userId]);
    }

    //Use Case: mentorship pairing
    public function requestMentorship($mentorId) {
        $stmt = $this->db->prepare("INSERT INTO ratings (fromUserId, toUserId, score, comment) VALUES (?, ?, 0, 'Mentorship request')");
        return $stmt->execute([$this->userId, $mentorId]);
    }
}
?>