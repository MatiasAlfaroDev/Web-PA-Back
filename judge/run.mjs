// Runs student code against a batch of stdin/stdout test cases.
// Input on stdin: {"code": "...", "tests": [{"stdin": "..."}], "timeoutMs": 3000}
// Output on stdout: [{"stdout": "...", "error": "..."|null}, ...] one per test.
//
// ponytail: node:vm sandbox (timeout + no injected require/process/fs), not a
// hard security boundary like isolated-vm — a determined script can still
// escape via constructor chains or block via async work the timeout can't see.
// Fine for a course platform grading well-formed stdin/stdout snippets.
// Upgrade to isolated-vm if this ever needs to hold against adversarial code
// (needs a C++20-capable toolchain, which this box's VS2019 Build Tools lack).
import vm from 'node:vm';

function runOne(code, stdin, timeoutMs) {
    const output = [];
    const sandbox = {
        stdin: stdin ?? '',
        console: { log: (...args) => output.push(args.map(String).join(' ')) },
    };
    vm.createContext(sandbox);

    try {
        vm.runInContext(code, sandbox, { timeout: timeoutMs });

        return { stdout: output.join('\n'), error: null };
    } catch (e) {
        return { stdout: output.join('\n'), error: String(e.message ?? e) };
    }
}

function readStdin() {
    return new Promise((resolve) => {
        let data = '';
        process.stdin.setEncoding('utf8');
        process.stdin.on('data', (chunk) => (data += chunk));
        process.stdin.on('end', () => resolve(data));
    });
}

const { code, tests, timeoutMs } = JSON.parse(await readStdin());
const results = tests.map((test) => runOne(code, test.stdin, timeoutMs ?? 3000));
process.stdout.write(JSON.stringify(results));
