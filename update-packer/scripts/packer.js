const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const archiver = require('archiver');

function packDiff(oldTag, newTag, progressCallback) {
	return new Promise((resolve) => {
		const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
		const zipName = `update_${oldTag}_to_HEAD_${dateStr}.zip`;

		const tempDir = path.join(__dirname, '..', 'update_temp');
		const zipPath = path.join(__dirname, '..', zipName);
		const deleteListPath = path.join(__dirname, '..', 'delete_list.txt');

		if (fs.existsSync(tempDir)) fs.rmSync(tempDir, { recursive: true });
		fs.mkdirSync(tempDir);
		if (fs.existsSync(zipPath)) fs.unlinkSync(zipPath);
		if (fs.existsSync(deleteListPath)) fs.unlinkSync(deleteListPath);

		let changes;
		try {
			execSync(`git diff --name-status ${oldTag} ${newTag} > changed_files.txt`);
			changes = fs.readFileSync('changed_files.txt', 'utf-8').trim().split('\n');
			fs.unlinkSync('changed_files.txt');
		} catch (err) {
			return resolve({ success: false, message: `❌ Git 标签错误或版本不存在：${err.message}` });
		}

		const deletions = [];
		for (const line of changes) {
			if (!line.trim()) continue;
			const [status, file] = line.split(/\t/);
			if (!file) continue;
			if (status === 'D') {
				deletions.push(file);
				progressCallback(`标记删除：${file}`);
			} else {
				const fullPath = path.resolve(file);
				if (fs.existsSync(fullPath)) {
					const dest = path.join(tempDir, file);
					fs.mkdirSync(path.dirname(dest), { recursive: true });
					fs.copyFileSync(fullPath, dest);
					progressCallback(`收集文件：${file}`);
				}
			}
		}

		if (deletions.length > 0) {
			fs.writeFileSync(deleteListPath, deletions.join('\n'));
			progressCallback(`🧹 删除清单写入 ${deleteListPath}`);
		}

		const output = fs.createWriteStream(zipPath);
		const archive = archiver('zip');
		archive.pipe(output);
		archive.directory(tempDir, false);
		archive.finalize().then(() => {
			fs.rmSync(tempDir, { recursive: true });
			progressCallback(`✅ 已生成更新包：${zipPath}`);
			resolve({ success: true, message: `✅ 更新包已生成：${zipPath}` });
		});
	});
}

module.exports = { packDiff };