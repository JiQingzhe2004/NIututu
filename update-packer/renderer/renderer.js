document.addEventListener('DOMContentLoaded', async () => {
	const oldTagSelect = document.getElementById('oldTag');
	const runBtn = document.getElementById('runBtn');
	const logArea = document.getElementById('log');

	function log(message) {
		logArea.value += message + '\n';
		logArea.scrollTop = logArea.scrollHeight;
	}

	log('正在读取 Git 标签...');
	const tags = await window.api.getTags();
	tags.reverse().forEach(tag => {
		const option = document.createElement('option');
		option.value = tag;
		option.textContent = tag;
		oldTagSelect.appendChild(option);
	});

	window.api.onProgress((msg) => {
		log(msg);
	});

	runBtn.addEventListener('click', async () => {
		const oldTag = oldTagSelect.value;
		if (!oldTag) return alert('请选择旧版本');
		const newTag = 'HEAD';

		log(`开始打包：从 ${oldTag} 到最新提交（HEAD）`);
		runBtn.disabled = true;
		const result = await window.api.runPack(oldTag, newTag);
		runBtn.disabled = false;
		log(result.message || '完成');
	});
});