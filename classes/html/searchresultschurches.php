<?php

namespace Html;

class SearchResultsChurches extends Html {

    public $template = 'search/resultsChurches.twig';

    public function __construct() {
        parent::__construct();
        global $user, $config;

        $this->input = $_REQUEST;

        $this->setTitle('Templom keresése');

        //Beginning of new Search Egine
        $search = \Eloquent\Church::where('ok', 'i');
        if ($this->input['kulcsszo']) {
            $keyword = preg_replace("/\*/", "%", $this->input['kulcsszo']);
            $search->whereShortcutLike($keyword, 'name');
        }
        if ($this->input['varos']) {
            $keyword = preg_replace("/\*/", "%", $this->input['varos']);
            $search->whereShortcutLike($keyword, 'administrative');
        }

        //Data For _panelSearchForChurch.twig
        $this->form['varos']['value'] = $_REQUEST['varos'];
        $this->form['kulcsszo']['value'] = $_REQUEST['kulcsszo'];
        $selectReligiousAdministration = \Form::religiousAdministrationSelection(['diocese' => $_REQUEST['ehm'], 'deanery' => $_REQUEST['espker']]);
        $this->form['diocese'] = $selectReligiousAdministration['dioceses'];
        $this->form['diocese']['name'] = 'ehm';
        $this->form['deaneries'] = $selectReligiousAdministration['deaneries'];
        foreach ($this->form['deaneries'] as &$form) {
            $form['name'] = 'espker';
        }

        //Old Search Engine
        $offset = $this->pagination->take * $this->pagination->active;
        $limit = $this->pagination->take;
        $results = searchChurches($_REQUEST, $offset, $limit);
        $resultsCount = $results['sum'];

        if (!isset($_REQUEST['gorog']))
            $_REQUEST['gorog'] = false;

        //Data For Pagination
        $params = ['varos' => $_REQUEST['varos'], 'tavolsag' => $_REQUEST['tavolsag'],
            'hely' => $_REQUEST['hely'], 'kulcsszo' => $_REQUEST['kulcsszo'],
            'gorog' => $_REQUEST['gorog'], 'tnyelv' => $_REQUEST['tnyelv'],
            'espker' => $_REQUEST['espker'], 'ehm' => $_REQUEST['ehm'],
            'q' => 'SearchResultsChurches'];
        $url = \Pagination::qe($params);
        $this->pagination->set($resultsCount, $url);

        if ($resultsCount < 1) {
            addMessage('A keresés nem hozott eredményt', 'info');
            return;
        } else if ($resultsCount == 1) {
            $url = '/templom/' . $results['results'][0]['id'];
            $event = ['Search', 'fast', $_REQUEST['varos'] . $_REQUEST['kulcsszo'] . $_REQUEST['e']];
            $this->redirectWithAnalyticsEvent($url, $event);
            return;
        } elseif ($resultsCount < $this->pagination->take * $this->pagination->active) {
            addMessage('Csupán ' . $resultsCount . " templomot találtunk.", 'info');
            return;
        }

        foreach ($results['results'] as $result) {
            $churchIds[] = $result['id'];
        }
        $this->churches = \Eloquent\Church::whereIn('id', $churchIds)->get();
    }

}
