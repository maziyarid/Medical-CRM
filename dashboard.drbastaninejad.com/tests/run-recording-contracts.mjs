// Optional offline runner. Uses an ALREADY installed official PHP WASM runtime.
// No installation, download, server, microphone, credentials, or patient data.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
const runtimeRoot = process.env.PHP_WASM_RUNTIME_ROOT;
if (!runtimeRoot) throw new Error('Set PHP_WASM_RUNTIME_ROOT to an existing runtime installation, or run php tests/recording-contracts.php.');
const { loadNodeRuntime } = await import(pathToFileURL(path.join(runtimeRoot, 'node_modules/@php-wasm/node/index.js')).href);
const { PHP } = await import(pathToFileURL(path.join(runtimeRoot, 'node_modules/@php-wasm/universal/index.js')).href);
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const php = new PHP(await loadNodeRuntime(process.env.PHP_VERSION || '8.3', { emscriptenOptions: { processId: 137 } }));
const files = ['tests/recording-contracts.php', ...fs.readdirSync(path.join(root, 'app/Services/Recording')).filter(name => name.endsWith('.php')).map(name => `app/Services/Recording/${name}`)];
try {
  for (const file of files) {
    const target = `/repo/${file}`;
    let current = '';
    for (const part of path.dirname(target).split('/').filter(Boolean)) {
      current += `/${part}`;
      if (!php.fileExists(current)) php.mkdir(current);
    }
    php.writeFile(target, fs.readFileSync(path.join(root, file)));
  }
  const interchange = process.argv.includes('--interchange');
  let response;
  try { response = await php.run(interchange ? { code: `<?php define('RECORDING_EMIT_INTERCHANGE', true); require '/repo/tests/recording-contracts.php';` } : { scriptPath: '/repo/tests/recording-contracts.php' }); }
  catch (error) { if (!error.response) throw error; response = error.response; }
  if (!interchange) {
    const version = await php.run({ code: '<?php echo PHP_VERSION;' });
    console.log(`PHP WASM version: ${version.text}`);
  }
  console.log(response.text);
  if (response.errors) console.error(response.errors);
  process.exitCode = response.exitCode;
} finally { php.exit(); }
