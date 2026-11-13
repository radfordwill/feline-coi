<?php
/** Pedigree kinship engine. No WordPress dependency. */
final class FCOI_Engine {
    private $cats = array();
    private $rank = array();
    private $memo = array();
    private $state = array();
    private $depth = array();

    public function __construct($rows) {
        if (!is_array($rows) || count($rows) > 500) {
            throw new InvalidArgumentException('Provide at most 500 cats.');
        }
        $registrations = array();
        foreach ($rows as $row) {
            if (!is_array($row)) { throw new InvalidArgumentException('Invalid cat record.'); }
            foreach (array('id', 'name', 'sex', 'sire', 'dam') as $key) {
                if (!isset($row[$key]) || !is_string($row[$key])) {
                    throw new InvalidArgumentException('Every cat needs text fields: id, name, sex, sire, dam.');
                }
            }
            if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $row['id']) || isset($this->cats[$row['id']])) {
                throw new InvalidArgumentException('Cat IDs must be unique, with 1–64 letters, digits, underscores or hyphens.');
            }
            if (trim($row['name']) === '' || strlen($row['name']) > 200 || !in_array($row['sex'], array('M','F','U'), true)) {
                throw new InvalidArgumentException('Enter a name of at most 200 bytes and a valid sex.');
            }
            $reg = isset($row['registration']) ? $row['registration'] : '';
            if (!is_string($reg) || strlen($reg) > 80 || ($reg !== '' && isset($registrations[strtoupper(trim($reg))]))) {
                throw new InvalidArgumentException('Registration numbers must be unique and at most 80 bytes.');
            }
            $row['registration'] = trim($reg);
            if ($row['registration'] !== '') { $registrations[strtoupper($row['registration'])] = true; }
            $this->cats[$row['id']] = array_intersect_key($row, array_flip(array('id','name','sex','sire','dam','registration')));
        }
        foreach ($this->cats as $id => $cat) {
            if ($cat['sire'] !== '' && $cat['sire'] === $cat['dam']) { throw new InvalidArgumentException('Sire and dam must be different cats.'); }
            foreach (array('sire' => 'M', 'dam' => 'F') as $role => $sex) {
                $parent = $cat[$role];
                if ($parent !== '' && (!isset($this->cats[$parent]) || $parent === (string)$id)) {
                    throw new InvalidArgumentException('Parents must be existing cats and cannot be the cat itself.');
                }
                if ($parent !== '' && $this->cats[$parent]['sex'] !== $sex && $this->cats[$parent]['sex'] !== 'U') {
                    throw new InvalidArgumentException('A sire must be male or unknown; a dam must be female or unknown.');
                }
            }
        }
        foreach (array_keys($this->cats) as $id) { $this->visit($id, 0); }
    }
    private function visit($id, $level) {
        if ($level > 50) { throw new InvalidArgumentException('Pedigrees deeper than 50 generations are not supported.'); }
        if (isset($this->state[$id]) && $this->state[$id] === 1) { throw new InvalidArgumentException('Pedigree contains an ancestry cycle.'); }
        if (isset($this->state[$id]) && $this->state[$id] === 2) { return; }
        $this->state[$id] = 1;
        $depth = 0;
        foreach (array('sire','dam') as $role) {
            $p = $this->cats[$id][$role];
            if ($p !== '') { $this->visit($p, $level + 1); $depth = max($depth, 1 + $this->depth[$p]); }
        }
        if ($depth > 50) { throw new InvalidArgumentException('Pedigrees deeper than 50 generations are not supported.'); }
        $this->depth[$id] = $depth;
        $this->rank[$id] = count($this->rank);
        $this->state[$id] = 2;
    }
    public function rows() { return array_values($this->cats); }
    private function kinship($a, $b) {
        if ($a === '' || $b === '') { return 0.0; }
        if ($this->rank[$a] < $this->rank[$b]) { $tmp = $a; $a = $b; $b = $tmp; }
        $key = $this->rank[$a] . ':' . $this->rank[$b];
        if (isset($this->memo[$key])) { return $this->memo[$key]; }
        $cat = $this->cats[$a];
        if ($a === $b) {
            $value = (1 + $this->kinship($cat['sire'], $cat['dam'])) / 2;
        } else {
            $value = ($this->kinship($cat['sire'], $b) + $this->kinship($cat['dam'], $b)) / 2;
        }
        $this->memo[$key] = $value;
        return $value;
    }
    public function calculate($id, $dam = null) {
        if (!isset($this->cats[$id]) || ($dam !== null && !isset($this->cats[$dam]))) { throw new InvalidArgumentException('Select existing cats.'); }
        if ($dam !== null) {
            if ($id === $dam || $this->cats[$id]['sex'] === 'F' || $this->cats[$dam]['sex'] === 'M') { throw new InvalidArgumentException('Select different cats with appropriate sire and dam roles.'); }
            $coi = $this->kinship($id, $dam);
            $front = array($id, $dam);
            $depth = 1 + max($this->depth[$id], $this->depth[$dam]);
        } else {
            $coi = $this->kinship($this->cats[$id]['sire'], $this->cats[$id]['dam']);
            $front = array($this->cats[$id]['sire'], $this->cats[$id]['dam']);
            $depth = $this->depth[$id];
        }
        $coverage = array();
        for ($g = 1; $g <= 5; $g++) {
            $known = 0; $next = array();
            foreach ($front as $p) {
                if ($p !== '') { $known++; $next[] = $this->cats[$p]['sire']; $next[] = $this->cats[$p]['dam']; }
                else { $next[] = ''; $next[] = ''; }
            }
            $coverage[] = array('generation' => $g, 'known' => $known, 'possible' => pow(2, $g));
            $front = $next;
        }
        return array('coi' => $coi, 'percent' => 100 * $coi, 'max_generations' => $depth, 'coverage' => $coverage,
            'method' => 'Pedigree kinship recursion; all recorded ancestors; unknown founders assumed unrelated and non-inbred.');
    }
}
