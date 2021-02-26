<?php

namespace Cleanse\Streaming\Controllers;

use Flash;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use ApplicationException;
use Cleanse\Streaming\Models\Streamer;

class Streamers extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['cleanse.streaming.access_streamers'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext(
            'Cleanse.Streaming',
            'twitch',
            'streamers'
        );
    }

    public function index()
    {
        $this->vars['streamersTotal'] = Streamer::count();

        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        BackendMenu::setContextSideMenu('new_streamer');

        $this->bodyClass = 'compact-container';

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';

        return $this->asExtension('FormController')->update($recordId);
    }
}
