<?php
class Seed {
    private $db;
    public $seedId, $name, $expiryDate, $type, $parentSeedId, $plotId;

    public function __construct($seedId = null) {
        $this->db = Database::getInstance()->getConnection();
        if($seedId) $this->load($seedId);
    }

    public function checkExpiry() {
        $today = new DateTime();
        $expiry = new DateTime($this->expiryDate);
        return $expiry > $today;
    }

    public function linkParentSeed($parentId) {
        $stmt = $this->db->prepare("UPDATE seeds SET parentSeedId = ? WHERE seedId = ?");
        return $stmt->execute([$parentId, $this->seedId]);
    }

    public function getParentSeed() {
        if(!$this->parentSeedId) return null;
        return new Seed($this->parentSeedId);
    }

    // Use Case: genetic lineage logger
    public function getLineage() {
        $lineage = [];
        $current = $this;
        while($current->parentSeedId) {
            $parent = $current->getParentSeed();
            $lineage[] = $parent;
            $current = $parent;
        }
        return $lineage;
    }

    private function load($seedId) {
        $stmt = $this->db->prepare("SELECT * FROM seeds WHERE seedId = ?");
        $stmt->execute([$seedId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if($data) {
            foreach($data as $key => $val) $this->$key = $val;
        }
    }
}
?>