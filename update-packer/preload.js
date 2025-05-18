const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('api', {
	getTags: () => ipcRenderer.invoke('get-tags'),
	runPack: (oldTag, newTag) => ipcRenderer.invoke('run-pack', oldTag, newTag),
	onProgress: (callback) => ipcRenderer.on('progress', (_, msg) => callback(msg))
});