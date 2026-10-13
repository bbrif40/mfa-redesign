"""Isolated integration checks; uses only Python's standard library and a PHP CLI."""
import concurrent.futures
import hashlib
import html
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.parse
import urllib.request


class Browser:
    def __init__(self, base):
        self.base = base
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.page = ''
        self.url = ''

    def get(self, path):
        with self.opener.open(urllib.parse.urljoin(self.base, path), timeout=10) as response:
            self.page = response.read().decode()
            self.url = response.url
        return self.page

    def post(self, path, **fields):
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', self.page)
        if csrf:
            fields['csrf'] = csrf.group(1)
        data = urllib.parse.urlencode(fields).encode()
        with self.opener.open(urllib.parse.urljoin(self.base, path), data, timeout=10) as response:
            self.page = response.read().decode()
            self.url = response.url
        return self.page

    def login(self, email, failure=False):
        self.get('index.php')
        fields = dict(email=email, password='dummy')
        if failure:
            fields['simulate_failure'] = '1'
        self.post('index.php', **fields)
        return urllib.parse.parse_qs(urllib.parse.urlparse(self.url).query)['t'][0]

    def pick_link(self, token):
        self.get('verify.php?t=' + token)
        number = re.search(r'aria-label="Your sign-in number">(\d+)', self.page).group(1)
        self.get('demo-email.php?t=' + token)
        links = [html.unescape(link) for link in re.findall(r'href="([^"]*pick.php[^"]*)"', self.page)]
        return next(link for link in links if urllib.parse.parse_qs(urllib.parse.urlparse(link).query)['n'][0] == number)

    def approve(self, token):
        link = self.pick_link(token)
        self.get(link)
        self.post(link)
        assert 'SIGN-IN APPROVED' in self.page
        self.get('done.php?t=' + token)


def main():
    php = str(Path(sys.argv[1]).resolve())
    root = Path(__file__).resolve().parents[1]
    with tempfile.TemporaryDirectory(prefix='mfa-check-') as directory:
        site = Path(directory)
        for source in (root / 'htdocs').glob('*'):
            if source.is_file() and source.name != 'config.php':
                shutil.copy(source, site / source.name)
        (site / 'data').mkdir()
        with socket.socket() as listener:
            listener.bind(('127.0.0.1', 0))
            port = listener.getsockname()[1]
        base = f'http://127.0.0.1:{port}/'
        config = (site / 'config.example.php').read_text().replace('http://127.0.0.1:8080', base.rstrip('/'))
        (site / 'config.php').write_text(config)
        environment = dict(os.environ, MFA_DEV='1')
        with (site / 'server.log').open('w') as server_log:
            server = subprocess.Popen([php, '-S', f'127.0.0.1:{port}', '-t', str(site), str(root / 'router.php')], env=environment, stdout=server_log, stderr=server_log)
            try:
                browser = Browser(base)
                for _ in range(50):
                    try:
                        browser.get('index.php')
                        break
                    except OSError:
                        time.sleep(.1)
                email = 'integration@example.test'
                token = browser.login(email)
                for path, expected in [('favicon.ico', 404), ('data/last_email.html', 403)]:
                    try:
                        browser.get(path)
                        raise AssertionError('Local router must reject this path')
                    except urllib.error.HTTPError as error:
                        assert error.code == expected
                browser.approve(token)
                assert 'SET UP YOUR SECURITY CODE' in browser.page
                setup_url = browser.url
                browser.post(setup_url, action='generate')
                codes = re.findall(r'<code>([A-F0-9-]+)</code>', browser.page)
                assert len(codes) == 1
                code = codes[0]
                browser.post(setup_url, action='generate')
                assert re.findall(r'<code>([A-F0-9-]+)</code>', browser.page) == codes
                account_path = site / 'data' / ('acct_' + hashlib.sha1(email.encode()).hexdigest() + '.json')
                assert code not in account_path.read_text()
                browser.post(setup_url, action='acknowledge')
                assert 'SET UP YOUR SECURITY CODE' in browser.page
                browser.post(setup_url, action='acknowledge', saved='1')
                assert 'Dashboard' in browser.page
                original_hashes = json.loads(account_path.read_text())['recovery_hashes']
                print('PASS: one hashed permanent code, required saved acknowledgement, no regeneration during setup')

                browser.post(browser.url, action='helpdesk')
                assert "alert('Simulation:" in browser.page and 'Your code remains unchanged' in browser.page
                assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                browser.post(browser.url, action='replace')
                browser.get(setup_url)
                assert 'Dashboard' in browser.page
                assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                browser.post(browser.url, action='prefer_security')
                for _ in range(3):
                    browser.login(email)
                    assert 'USE A SECURITY CODE' in browser.page
                    browser.post(browser.url, action='code', code=code.lower().replace('-', ' '))
                    assert 'Signed in with a security code' in browser.page
                    assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                print('PASS: same code works across repeated preferred logins; dashboard helpdesk and old replacement routes cannot change it')

                browser.post(browser.url, action='prefer_email')
                token = browser.login(email)
                old_link = browser.pick_link(token)
                browser.get('verify.php?t=' + token)
                browser.post(browser.url, action='backup')
                backup_url = browser.url
                assert 'Try email verification' not in browser.page
                assert 'value="email"' not in browser.page
                browser.post(backup_url, action='email')
                assert browser.url == backup_url and 'USE A SECURITY CODE' in browser.page
                backup_token = urllib.parse.parse_qs(urllib.parse.urlparse(backup_url).query)['t'][0]
                assert json.loads((site / 'data' / ('req_' + backup_token + '.json')).read_text())['status'] == 'pending'
                browser.get(old_link)
                assert 'LINK NO LONGER ACTIVE' in browser.page
                browser.get(backup_url)
                browser.post(backup_url, action='helpdesk')
                assert "alert('Simulation:" in browser.page and 'Your code remains unchanged' in browser.page
                browser.post(backup_url, action='replace')
                assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                browser.post(backup_url, action='code', code=code)
                assert 'Dashboard' in browser.page
                print('PASS: switching cancels delayed email approval; lost-code request only acknowledges and never changes the code')

                token = browser.login(email, failure=True)
                assert 'mail_failed' in (site / 'data' / ('req_' + token + '.json')).read_text()
                browser.post(browser.url, action='code', code='wrong')
                assert 'ACCOUNT PROTECTION LOCKED' in browser.page
                account = json.loads(account_path.read_text())
                assert 115 <= account['locked_until'] - time.time() <= 120
                account['preferred_verification'] = 'security_code'
                account_path.write_text(json.dumps(account))
                browser.get('index.php')
                browser.post('index.php', email=email, password='dummy')
                assert 'ACCOUNT PROTECTION LOCKED' in browser.page
                account['locked_until'] = int(time.time()) - 1
                account_path.write_text(json.dumps(account))
                browser.login(email)
                browser.post(browser.url, action='code', code='wrong')
                account = json.loads(account_path.read_text())
                assert 415 <= account['locked_until'] - time.time() <= 420
                account['locked_until'] = int(time.time()) - 1
                account_path.write_text(json.dumps(account))
                browser.login(email)
                browser.post(browser.url, action='code', code=code)
                assert 'Dashboard' in browser.page
                assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                print('PASS: incorrect codes keep existing two/seven-minute lockouts; valid code remains usable afterward')

                first, second = Browser(base), Browser(base)
                tokens = [first.login(email), second.login(email)]
                stranger = Browser(base)
                stranger.get('recovery.php?t=' + tokens[0])
                assert 'LOG IN' in stranger.page

                def approve(token):
                    script = "require 'lib.php'; $t=$argv[1]; $r=request($t); $_COOKIE['mfa_bind']=$r['bind']; echo verify_recovery_code($t,$argv[2]);"
                    return subprocess.check_output([php, '-r', script, token, code], cwd=site, env=environment, text=True).strip()
                with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                    assert list(pool.map(approve, tokens)) == ['approved', 'approved']
                assert json.loads(account_path.read_text())['recovery_hashes'] == original_hashes
                print('PASS: browser ownership retained; concurrent valid logins do not consume or rotate the permanent code')

                demo = Browser(base)
                demo.get('demo.php')
                demo_csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', demo.page).group(1)
                demo.post('demo.php')
                demo_codes = re.findall(r'<code>([A-F0-9-]+)</code>', demo.page)
                assert len(demo_codes) == 1
                demo.post('demo.php', csrf=demo_csrf)
                assert re.findall(r'<code>([A-F0-9-]+)</code>', demo.page) == demo_codes
                legacy_email = 'legacy@example.test'
                legacy_path = site / 'data' / ('acct_' + hashlib.sha1(legacy_email.encode()).hexdigest() + '.json')
                legacy_hashes = [hashlib.sha256(value.encode()).hexdigest() for value in ['FIRST', 'SECOND']]
                legacy_path.write_text(json.dumps(dict(recovery_hashes=legacy_hashes, recovery_ready=True, preferred_verification='security_code')))
                Browser(base).login(legacy_email)
                assert json.loads(legacy_path.read_text())['recovery_hashes'] == legacy_hashes[:1]
                print('PASS: demo preparation cannot replace existing code; legacy account keeps its first remaining code')

                production_environment = dict(environment, MFA_DEV='0')
                login_html = subprocess.check_output([php, '-r', "$_SERVER['REQUEST_METHOD']='GET'; include 'index.php';"], cwd=site, env=production_environment, text=True)
                assert 'simulate_failure' not in login_html and 'demo.php' not in login_html
                for endpoint in ['demo.php', 'demo-email.php']:
                    assert subprocess.check_output([php, '-r', f"include '{endpoint}';"], cwd=site, env=production_environment, text=True) == 'Not found'
                assert not re.search(r'PHP (Warning|Fatal error|Deprecated)', (site / 'server.log').read_text())
                print('PASS: simulation controls are restricted to demo mode')
            finally:
                server.terminate()
                server.wait(timeout=10)
    print('All permanent security-code checks passed.')


if __name__ == '__main__':
    main()
