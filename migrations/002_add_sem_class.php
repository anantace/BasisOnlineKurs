<?php



class AddSemClass extends Migration
{
    public function description () {
        return 'add SemClass and SemTypes whose courses use this plugin for a simplified courseview';
    }


    public function up () {
        $id = $this->insertSemClass();
        $this->addSemTypes($id);
        $this->addConfigOption($id);
        SimpleORMap::expireTableScheme();
    }


    public function down () {
        $id = $this->getMoocSemClassID();
        $this->removeSemClassAndTypes($id);
        $this->removeConfigOption();
        SimpleORMap::expireTableScheme();
    }

    /**********************************************************************/
    /* PRIVATE METHODS                                                    */
    /**********************************************************************/

    private function getMoocSemClassID()
    {
        return Config::get()->getValue('MiniCourse_SEM_CLASS_CONFIG_ID');
    }

    private function insertSemClass()
    {
        $db = DBManager::get();
        $name = 'BasisOnlineKurs'; //\Mooc\SEM_CLASS_NAME;

        $this->validateUniqueness($name);

        $statement = $db->prepare("INSERT INTO sem_classes SET name = ?, mkdate = UNIX_TIMESTAMP(), chdate = UNIX_TIMESTAMP()");
        $statement->execute(array($name));

        $id = $db->lastInsertId();

        $sem_class = SemClass::getDefaultSemClass();
        $sem_class->set('name', $name);
        $sem_class->set('id', $id);

	 //$sem_class->set('overview', NULL);
	 //$sem_class->set('forum', NULL);
	 //$sem_class->set('id', $id);
	 //$sem_class->set('id', $id);
	 //$sem_class->set('id', $id);

        $sem_class->store();

        $GLOBALS['SEM_CLASS'] = SemClass::refreshClasses();

        return $id;
    }

    private function validateUniqueness($name)
    {
        $statement = DBManager::get()->prepare('SELECT id FROM sem_classes WHERE name = ?');
        $statement->execute(array($name));
        if ($old = $statement->fetchColumn()) {
            $message = sprintf('Es existiert bereits eine Veranstaltungskategorie mit dem Namen "%s" (id=%d)', htmlspecialchars($name), $old);
            throw new Exception($message);
        }
    }

    private function addSemTypes($sc_id)
    {
        $db = DBManager::get();
        $statement = $db->prepare(
            "INSERT INTO sem_types SET name = ?, class = ?, mkdate = UNIX_TIMESTAMP(), chdate = UNIX_TIMESTAMP()");

		$name = "BasisOnlineKurs";
        //foreach (words(\MiniCourse\SEM_TYPE_NAMES) as $name) {
            $statement->execute(array($name, $sc_id));
        //}
        $GLOBALS['SEM_TYPE'] = SemType::refreshTypes();
    }

    private function addConfigOption($sc_id)
    {
        Config::get()->create('MiniCourse_SEM_CLASS_CONFIG_ID', array(
            'value'       => $sc_id,
            'is_default'  => 0,
            'type'        => 'integer',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'ID der Veranstaltungsklasse f�r BasisOnlineKurs-Veranstaltungen.'
            ));
    }

    private function removeSemClassAndTypes($id)
    {
        $sem_class = new SemClass(intval($id));
        $sem_class->delete();
        $GLOBALS['SEM_CLASS'] = SemClass::refreshClasses();
    }

    private function removeConfigOption()
    {
        return Config::get()->delete('MiniCourse_SEM_CLASS_CONFIG_ID');
    }
}
