"""Serve isolated real-browser JS tests on localhost, without production forms."""
from pathlib import Path
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
import argparse
import json
import tempfile
import urllib.parse

ROOT = Path(__file__).resolve().parents[2]
ROUTES = {
    '/jquery.js': ROOT / 'static/js/jquery.js',
    '/global.js': ROOT / 'static/js/global.js',
    '/checkForm.js': ROOT / 'static/js/checkForm.js',
    '/fixture.js': ROOT / 'tests/javascript/javascript_browser_fixture.js',
    '/static/images/loading.gif': ROOT / 'static/images/loading.gif',
}


class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def do_GET(self):
        parts = urllib.parse.urlsplit(self.path)
        if parts.path == '/':
            text = (ROOT / 'tests/javascript/javascript_browser_fixture.html').read_text()
            scripts = '' if parts.query == 'standalone' else '<script src="/global.js"></script>'
            data = text.replace('<!--GLOBAL-->', scripts).encode()
            mime = 'text/html; charset=utf-8'
        elif parts.path in ROUTES:
            data = ROUTES[parts.path].read_bytes()
            mime = 'image/gif' if parts.path.endswith('.gif') else 'application/javascript; charset=utf-8'
        elif parts.path == '/echo':
            data = json.dumps({'requestedWith': self.headers.get('X-Requested-With')}).encode()
            mime = 'application/json'
        elif parts.path == '/favicon.ico':
            self.send_response(204)
            self.end_headers()
            return
        else:
            self.send_error(404)
            return
        self.send_response(200)
        self.send_header('Content-Type', mime)
        self.send_header('Cache-Control', 'no-store')
        self.end_headers()
        self.wfile.write(data)

    def do_POST(self):
        if self.path != '/report':
            self.send_error(404)
            return
        origin = self.headers.get('Origin')
        if origin and origin != 'http://127.0.0.1:' + str(self.server.server_port):
            self.send_error(403)
            return
        try:
            length = int(self.headers.get('Content-Length', '0'))
            if not 0 < length <= 1048576:
                raise ValueError('Invalid report size')
            data = json.loads(self.rfile.read(length))
            if data.get('mode') not in ('integrated', 'standalone'):
                raise ValueError('Invalid report mode')
        except (ValueError, TypeError, AttributeError):
            self.send_error(400)
            return
        target = self.server.results_dir / ('browser-' + data['mode'] + '.json')
        target.write_text(json.dumps(data, ensure_ascii=False, indent=2))
        self.send_response(200)
        self.end_headers()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results-dir', help='Results folder; defaults to an OS temporary directory')
    args = parser.parse_args()
    results = Path(args.results_dir) if args.results_dir else Path(tempfile.mkdtemp(prefix='mystep-js-browser-'))
    results.mkdir(parents=True, exist_ok=True)
    server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
    server.results_dir = results
    url = 'http://127.0.0.1:' + str(server.server_port) + '/'
    (results / 'browser-url.txt').write_text(url)
    print('Integrated: ' + url + '\nStandalone: ' + url + '?standalone\nResults: ' + str(results), flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == '__main__':
    main()
