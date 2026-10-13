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
                try:
                    browser.get('favicon.ico')
                    raise AssertionError('Missing icon must return 404')
                except urllib.error.HTTPError as error:
                    assert error.code == 404
                try:
                    browser.get('data/last_email.html')
                    raise AssertionError('Runtime email must not be directly accessible')
                except urllib.error.HTTPError as error:
                    assert error.code == 403
                browser.approve(token)
                assert 'SET UP RECOVERY CODES' in browser.page and 'Dashboard' not in browser.page
                browser.post(browser.url, action='generate')
                codes = re.findall(r'<code>([A-F0-9-]+)</code>', browser.page)
                assert len(codes) == 5 and len(set(codes)) == 5
                account_path = site / 'data' / ('acct_' + hashlib.sha1(email.encode()).hexdigest() + '.json')
                assert all(code not in account_path.read_text() for code in codes)
                browser.post(browser.url, action='acknowledge')
                assert 'SET UP RECOVERY CODES' in browser.page
                browser.post(browser.url, action='acknowledge', saved='1')
                assert 'Dashboard' in browser.page and '5 recovery codes remaining' in browser.page
                print('PASS: first-time setup, five hashed codes, mandatory saved acknowledgement')

                token = browser.login(email)
                old_link = browser.pick_link(token)
                browser.get('verify.php?t=' + token)
                browser.post(browser.url, action='backup')
                backup_url = browser.url
                browser.get(old_link)
                assert 'LINK NO LONGER ACTIVE' in browser.page
                browser.get(backup_url)
                browser.post(backup_url, action='code', code=codes[0].lower().replace('-', ' '))
                assert 'Signed in with a recovery code' in browser.page and '4 recovery codes remaining' in browser.page
                print('PASS: switch cancels delayed email link; one code is consumed and dashboard shows method')

                token = browser.login(email, failure=True)
                failed_request = json.loads((site / 'data' / ('req_' + token + '.json')).read_text())
                assert failed_request['mail_failed'] and failed_request['method'] == 'recovery'
                assert json.loads(account_path.read_text())['hits'] == []
                backup_url = browser.url
                browser.post(backup_url, action='helpdesk')
                assert "alert('Simulation:" in browser.page
                assert json.loads((site / 'data' / ('req_' + token + '.json')).read_text())['status'] == 'pending'
                browser.post(backup_url, action='code', code=codes[0])
                assert 'ACCOUNT PROTECTION LOCKED' in browser.page
                account = json.loads(account_path.read_text())
                assert 115 <= account['locked_until'] - time.time() <= 120
                other = Browser(base)
                other.get('index.php')
                other.post('index.php', email=email, password='dummy', simulate_failure='1')
                assert 'ACCOUNT PROTECTION LOCKED' in other.page
                print('PASS: failed-send backup, receipt-only popup, reuse locks account and blocks new sign-ins')

                # Advance only isolated fixture storage to check escalation without waiting.
                account['locked_until'] = int(time.time()) - 1
                account_path.write_text(json.dumps(account))
                browser.login(email, failure=True)
                browser.post(browser.url, action='code', code='invalid')
                account = json.loads(account_path.read_text())
                assert 415 <= account['locked_until'] - time.time() <= 420
                account['locked_until'] = int(time.time()) - 1
                account_path.write_text(json.dumps(account))
                print('PASS: subsequent invalid code escalates lockout from two to seven minutes')

                token = browser.login(email, failure=True)
                browser.post(browser.url, action='replace')
                new_token = urllib.parse.parse_qs(urllib.parse.urlparse(browser.url).query)['t'][0]
                assert new_token != token
                browser.approve(new_token)
                assert 'SET UP RECOVERY CODES' in browser.page
                browser.post(browser.url, action='generate')
                new_codes = re.findall(r'<code>([A-F0-9-]+)</code>', browser.page)
                browser.post(browser.url, action='acknowledge', saved='1')
                assert '5 recovery codes remaining' in browser.page
                old_hashes = {hashlib.sha256(code.replace('-', '').encode()).hexdigest() for code in codes}
                assert not old_hashes.intersection(json.loads(account_path.read_text())['recovery_hashes'])
                token = browser.login(email)
                browser.approve(token)
                assert 'Dashboard' in browser.page and 'SET UP RECOVERY CODES' not in browser.page
                print('PASS: fresh email replacement revokes old set; later email sign-in retains codes')

                first = Browser(base)
                second = Browser(base)
                tokens = [first.login(email, failure=True), second.login(email, failure=True)]
                stranger = Browser(base)
                stranger.get('recovery.php?t=' + tokens[0])
                assert 'LOG IN' in stranger.page

                def consume(token):
                    code = "require 'lib.php'; $t=$argv[1]; $r=request($t); $_COOKIE['mfa_bind']=$r['bind']; echo verify_recovery_code($t,$argv[2]);"
                    return subprocess.check_output([php, '-r', code, token, new_codes[0]], cwd=site, env=environment, text=True).strip()
                with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                    results = list(pool.map(consume, tokens))
                assert results.count('approved') == 1 and results.count('locked') == 1, results
                assert len(json.loads(account_path.read_text())['recovery_hashes']) == 4
                print('PASS: browser ownership and concurrent consumption allow exactly one approval')

                demo = Browser(base)
                demo.get('demo.php')
                demo.post('demo.php')
                assert len(re.findall(r'<code>([A-F0-9-]+)</code>', demo.page)) == 5
                print('PASS: prepared fictional classroom demo account')
                production_environment = dict(environment, MFA_DEV='0')
                login_html = subprocess.check_output([php, '-r', "$_SERVER['REQUEST_METHOD']='GET'; include 'index.php';"], cwd=site, env=production_environment, text=True)
                assert 'simulate_failure' not in login_html and 'demo.php' not in login_html
                for endpoint in ['demo.php', 'demo-email.php']:
                    response = subprocess.check_output([php, '-r', f"include '{endpoint}';"], cwd=site, env=production_environment, text=True)
                    assert response == 'Not found'
                print('PASS: simulation controls and demo endpoints unavailable outside demo mode')
                log_text = (site / 'server.log').read_text()
                assert not re.search(r'PHP (Warning|Fatal error|Deprecated)', log_text), log_text
            finally:
                server.terminate()
                server.wait(timeout=10)
    print('All recovery-flow integration checks passed.')


if __name__ == '__main__':
    main()
