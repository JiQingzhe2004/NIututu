const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const { execSync } = require('child_process');
const { packDiff } = require('./scripts/packer');

function createWindow () {
	const win = new BrowserWindow({
		width: 800,
		height: 600,
		webPreferences: {
			preload: path.join(__dirname, 'preload.js'),
			contextIsolation: true,
			enableRemoteModule: false,
			nodeIntegration: false
		}
	});
	win.loadFile('renderer/index.html');
}

app.whenReady().then(() => {
	createWindow();
	app.on('activate', () => {
		if (BrowserWindow.getAllWindows().length === 0) createWindow();
	});
});

app.on('window-all-closed', () => {
	if (process.platform !== 'darwin') app.quit();
});

ipcMain.handle('get-tags', () => {
	try {
		const output = execSync('git tag').toString().split('\n').filter(Boolean);
		return output;
	} catch (err) {
		return [];
	}
});

ipcMain.handle('run-pack', async (_, oldTag, newTag) => {
	return await packDiff(oldTag, newTag, (msg) => {
		BrowserWindow.getAllWindows()[0].webContents.send('progress', msg);
	});
});