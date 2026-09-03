<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    // GET /admin/settings
    public function index(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $tab = $request->query('tab', 'general');
        $allowedTabs = ['general', 'notifications', 'tickets', 'security'];
        if (!in_array($tab, $allowedTabs)) {
            $tab = 'general';
        }

        $settings = Setting::getAll();
        $groups   = [];
        foreach ($allowedTabs as $group) {
            $groups[$group] = Setting::getGroup($group);
        }

        return $this->view('admin.settings.index', [
            'title'       => 'Portal Settings',
            'tab'         => $tab,
            'settings'    => $settings,
            'groups'      => $groups,
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Settings'],
            ],
        ]);
    }

    // POST /admin/settings
    public function update(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $tab  = $request->input('_tab', 'general');
        $data = $request->except(['_csrf_token', '_tab']);

        // Handle boolean fields — unchecked checkboxes aren't in POST
        $group = Setting::getGroup($tab);
        foreach ($group as $key => $def) {
            if ($def['type'] === 'boolean') {
                $data[$key] = isset($data[$key]) ? '1' : '0';
            }
        }

        // Basic validation
        if (isset($data['tickets_per_page'])) {
            $data['tickets_per_page'] = max(5, min(100, (int)$data['tickets_per_page']));
        }
        if (isset($data['max_attachment_size'])) {
            $data['max_attachment_size'] = max(1, min(50, (int)$data['max_attachment_size']));
        }
        if (isset($data['max_login_attempts'])) {
            $data['max_login_attempts'] = max(3, min(20, (int)$data['max_login_attempts']));
        }
        if (isset($data['session_lifetime'])) {
            $data['session_lifetime'] = max(15, min(1440, (int)$data['session_lifetime']));
        }

        Setting::setMany($data);
        Setting::clearCache();

        $this->session->success('Settings saved successfully.');
        $this->redirect(url("admin/settings?tab={$tab}"));
    }
}
