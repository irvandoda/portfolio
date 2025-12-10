import React, { useState, useEffect } from 'react';
import { fetchAPI } from '../utils/api';
import Toggle from '../components/Toggle';

function Settings() {
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);
  const [emergencyPassword, setEmergencyPassword] = useState('');
  const [emergencyPasswordConfirm, setEmergencyPasswordConfirm] = useState('');
  const [settingEmergencyPassword, setSettingEmergencyPassword] = useState(false);
  const [emergencyToken, setEmergencyToken] = useState(null);
  const [showToken, setShowToken] = useState(false);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const data = await fetchAPI('settings');
      setSettings(data);
    } catch (error) {
      console.error('Failed to load settings:', error);
      setMessage({ type: 'error', text: 'Failed to load settings' });
    } finally {
      setLoading(false);
    }
  };

  const saveSettings = async () => {
    setSaving(true);
    setMessage(null);

    try {
      await fetchAPI('settings', {
        method: 'POST',
        body: JSON.stringify({ settings }),
      });
      setMessage({ type: 'success', text: 'Settings saved successfully' });
    } catch (error) {
      console.error('Failed to save settings:', error);
      setMessage({ type: 'error', text: 'Failed to save settings' });
    } finally {
      setSaving(false);
    }
  };

  const handleToggle = (key, value) => {
    setSettings((prev) => ({ ...prev, [key]: value }));
  };

  const handleInputChange = (key, value) => {
    setSettings((prev) => ({ ...prev, [key]: value }));
  };

  const saveEmergencyPassword = async () => {
    if (!emergencyPassword) {
      setMessage({ type: 'error', text: 'Password cannot be empty' });
      return;
    }

    if (emergencyPassword !== emergencyPasswordConfirm) {
      setMessage({ type: 'error', text: 'Password confirmation does not match' });
      return;
    }

    setSettingEmergencyPassword(true);
    setMessage(null);

    try {
      const userId = settings.emergency_user_id || 1;
      await fetchAPI('emergency/password', {
        method: 'POST',
        body: JSON.stringify({ password: emergencyPassword, user_id: userId }),
      });
      setMessage({ type: 'success', text: 'Emergency password updated' });
      setEmergencyPassword('');
      setEmergencyPasswordConfirm('');
    } catch (error) {
      console.error('Failed to set emergency password:', error);
      setMessage({ type: 'error', text: 'Failed to set emergency password' });
    } finally {
      setSettingEmergencyPassword(false);
    }
  };

  const createEmergencyToken = async () => {
    try {
      const userId = 1; // Default to admin user
      const ttl = settings.emergency_token_ttl || 15;

      const data = await fetchAPI('emergency/create', {
        method: 'POST',
        body: JSON.stringify({ user_id: userId, ttl_minutes: ttl }),
      });

      setEmergencyToken(data);
      setShowToken(true);
    } catch (error) {
      console.error('Failed to create token:', error);
      setMessage({ type: 'error', text: 'Failed to create emergency token' });
    }
  };

  const testEmail = async () => {
    try {
      await fetchAPI('test/email', { method: 'POST' });
      setMessage({ type: 'success', text: 'Test email sent' });
    } catch (error) {
      console.error('Failed to send test email:', error);
      setMessage({ type: 'error', text: 'Failed to send test email' });
    }
  };

  if (loading) {
    return <div className="text-center py-8">Loading...</div>;
  }

  return (
    <div>
      <h2 className="text-2xl font-bold text-gray-900 mb-6">Settings</h2>

      {message && (
        <div className={`mb-4 p-4 rounded ${
          message.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
        }`}>
          {message.text}
        </div>
      )}

      {showToken && emergencyToken && (
        <div className="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <h3 className="font-semibold text-yellow-800 mb-2">Emergency Token Created</h3>
          <p className="text-sm text-yellow-700 mb-2">
            <strong>Warning:</strong> This token will only be shown once. Copy it now!
          </p>
          <div className="bg-white p-3 rounded border mb-2">
            <code className="text-sm break-all">{emergencyToken.token}</code>
          </div>
          <div className="text-sm text-yellow-700">
            <p><strong>URL:</strong> <a href={emergencyToken.url} className="text-blue-600 underline" target="_blank" rel="noopener noreferrer">{emergencyToken.url}</a></p>
            <p><strong>TTL:</strong> {emergencyToken.ttl} minutes</p>
          </div>
          <button
            onClick={() => setShowToken(false)}
            className="mt-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
          >
            Close
          </button>
        </div>
      )}

      <div className="bg-white shadow rounded-lg">
        <div className="px-4 py-5 sm:p-6 space-y-6">
          {/* Emergency Login Section */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">Emergency Login</h3>
            <div className="space-y-4">
              <Toggle
                label="Enable Emergency Login"
                checked={settings.emergency_enabled || false}
                onChange={(val) => handleToggle('emergency_enabled', val)}
              />
              <div>
                <label className="block text-sm font-medium text-gray-700">Custom URL Slug</label>
                <input
                  type="text"
                  value={settings.emergency_custom_url_slug || ''}
                  onChange={(e) => handleInputChange('emergency_custom_url_slug', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Token TTL (minutes)</label>
                <input
                  type="number"
                  value={settings.emergency_token_ttl || 15}
                  onChange={(e) => handleInputChange('emergency_token_ttl', parseInt(e.target.value))}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Emergency User ID</label>
                <input
                  type="number"
                  value={settings.emergency_user_id || 1}
                  onChange={(e) => handleInputChange('emergency_user_id', parseInt(e.target.value))}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  min={1}
                />
              </div>
              <Toggle
                label="One-Time Password"
                checked={settings.emergency_one_time || false}
                onChange={(val) => handleToggle('emergency_one_time', val)}
              />
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Emergency Password</label>
                  <input
                    type="password"
                    value={emergencyPassword}
                    onChange={(e) => setEmergencyPassword(e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Set a strong password"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Confirm Password</label>
                  <input
                    type="password"
                    value={emergencyPasswordConfirm}
                    onChange={(e) => setEmergencyPasswordConfirm(e.target.value)}
                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Re-type password"
                  />
                </div>
              </div>
              <button
                onClick={saveEmergencyPassword}
                disabled={settingEmergencyPassword}
                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
              >
                {settingEmergencyPassword ? 'Updating...' : 'Set Emergency Password'}
              </button>
              <button
                onClick={createEmergencyToken}
                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Create Emergency Token
              </button>
            </div>
          </div>

          <hr />

          {/* Detection Section */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">Login Detection</h3>
            <div className="space-y-4">
              <Toggle
                label="Enable Detection"
                checked={settings.detection_enabled || false}
                onChange={(val) => handleToggle('detection_enabled', val)}
              />
              <div>
                <label className="block text-sm font-medium text-gray-700">Mode</label>
                <select
                  value={settings.detection_mode || 'log'}
                  onChange={(e) => handleInputChange('detection_mode', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="log">Log Only</option>
                  <option value="protect">Protect</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Failed Login Threshold</label>
                <input
                  type="number"
                  value={settings.failed_login_threshold || 5}
                  onChange={(e) => handleInputChange('failed_login_threshold', parseInt(e.target.value))}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
          </div>

          <hr />

          {/* File Scan Section */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">File Integrity Scanner</h3>
            <div className="space-y-4">
              <Toggle
                label="Enable File Scan"
                checked={settings.file_scan_enabled || false}
                onChange={(val) => handleToggle('file_scan_enabled', val)}
              />
              <div>
                <label className="block text-sm font-medium text-gray-700">Mode</label>
                <select
                  value={settings.file_scan_mode || 'log'}
                  onChange={(e) => handleInputChange('file_scan_mode', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="log">Log Only</option>
                  <option value="protect">Protect</option>
                </select>
              </div>
            </div>
          </div>

          <hr />

          {/* Notifications Section */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">Notifications</h3>
            <div className="space-y-4">
              <Toggle
                label="Enable Notifications"
                checked={settings.notifications_enabled || false}
                onChange={(val) => handleToggle('notifications_enabled', val)}
              />
              <div>
                <label className="block text-sm font-medium text-gray-700">Notification Email</label>
                <input
                  type="email"
                  value={settings.notification_email || ''}
                  onChange={(e) => handleInputChange('notification_email', e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <button
                onClick={testEmail}
                className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              >
                Send Test Email
              </button>
            </div>
          </div>

          <div className="pt-4">
            <button
              onClick={saveSettings}
              disabled={saving}
              className="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {saving ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Settings;

