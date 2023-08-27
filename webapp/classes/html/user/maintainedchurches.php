<?php

namespace Html\User;

class MaintainedChurches extends \Html\Html {

    public function __construct() {
        $this->setTitle("Módosítható templomok és miserendek");
        $this->title = "Módosítható templomok és miserendek";

        global $user;
        if (!is_array($user->responsible['church'])) {
            addMessage("Nincs olyan templom, amit módosíthatnál.", 'info');
            return false;
        }

        foreach ($user->responsible['church'] as $tid) {
            try {
                $this->churches[$tid] = \Eloquent\Church::find($tid);
            } catch (\Exception $e) {
                addMessage($e->getMessage(), "info");
            }
        }

        $this->columns2 = true;
    }

}
