document.addEventListener('DOMContentLoaded', function () {
	let isFirstMessage = true;
	const input = document.getElementById('userInput');
	const history = document.getElementById('chatHistory');
	const logo = document.querySelector('.logo');
	const inputArea = document.querySelector('.input-area');
	const sidebar = document.querySelector('.sidebar');
	const mainContent = document.querySelector('.main-content');
	const toggleButton = document.querySelector('.toggle-btn');
	let conversationId = new URLSearchParams(window.location.search).get('conversation_id');

	// 削除確認モーダル
	const deleteModal = document.getElementById('deleteModal');
	const deleteMessage = document.getElementById('deleteMessage');
	const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
	const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
	let selectedConversationId = null;

	// **最新のメッセージまでスクロールする処理**
	function scrollToBottom() {
		setTimeout(() => {
			history.scrollTop = history.scrollHeight;
			console.log('Scrolled to bottom'); // デバッグ用ログ
		}, 100); // 少し遅延させてスクロール
	}

	// **エンターキーで送信する処理（Shift+Enterで改行）**
	input.addEventListener('keypress', function (event) {
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			sendMessage();
		}
	});

	// **メッセージ送信処理**
	async function sendMessage() {
		const message = input.value.trim();
		if (message === '') return;

		if (isFirstMessage) {
			logo.classList.add('small');
			inputArea.classList.remove('initial');
			inputArea.classList.add('bottom');
			isFirstMessage = false;
		}

		history.innerHTML += `<div class="user-message">${message}</div>`;
		scrollToBottom(); // **送信時にスクロール**

		const response = await fetch('chat.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ message: message, conversation_id: conversationId }),
		});

		const data = await response.json();
		setTimeout(() => {
			history.innerHTML += `<div class="bot-message">${data.reply}</div>`;
			scrollToBottom(); // **ボットの返信が来たらスクロール**
		}, 500);

		input.value = ''; // 送信後に入力欄をクリア
		scrollToBottom(); // **入力欄クリア後にスクロール**
	}

	// **サイドバーの状態をローカルストレージに保存・復元**
	function applySidebarState() {
		const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
		if (isSidebarOpen) {
			sidebar.classList.add('open');
			mainContent.classList.add('expanded');
		} else {
			sidebar.classList.remove('open');
			mainContent.classList.remove('expanded');
		}
	}

	function toggleSidebar() {
		const isNowOpen = sidebar.classList.toggle('open');
		mainContent.classList.toggle('expanded', isNowOpen);
		localStorage.setItem('sidebarOpen', isNowOpen); // 状態を保存
	}

	toggleButton.addEventListener('click', toggleSidebar);

	// **サイドバー以外をクリックで閉じる処理**
	document.addEventListener('click', function (event) {
		if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
			sidebar.classList.remove('open');
			mainContent.classList.remove('expanded');
			localStorage.setItem('sidebarOpen', false);
		}
	});

	// **ページ読み込み時にサイドバーの状態を復元**
	applySidebarState();

	// **現在の会話をハイライト**
	function highlightActiveConversation() {
		document.querySelectorAll('.conversation-item').forEach((item) => {
			item.classList.remove('active');
			if (item.dataset.conversationId === conversationId) {
				item.classList.add('active');
			}
		});
	}

	// **会話リストを更新**
	async function refreshConversationList() {
		try {
			const response = await fetch(`get_conversations.php`);
			const data = await response.json();

			const conversationList = document.getElementById('conversationList');
			conversationList.innerHTML = '';

			if (data.message) {
				conversationList.innerHTML = `<p class="no-conversation">${data.message}</p>`;
				return;
			}

			data.forEach((conversation) => {
				const isActive = conversation.conversation_id === conversationId ? 'active' : '';
				const listItem = document.createElement('li');
				listItem.className = `conversation-item ${isActive}`;
				listItem.dataset.conversationId = conversation.conversation_id;

				listItem.innerHTML = `
                    <span class="conversation-title-text" onclick="openConversation('${conversation.conversation_id}')">${conversation.title}</span>
                    <div class="menu-container">
                        <button class="menu-btn">⋮</button>
                        <div class="menu-content">
                            <button onclick="editTitle(this)">名前の変更</button>
                            <button onclick="showDeleteModal('${conversation.conversation_id}', '${conversation.title}')">削除</button>
                        </div>
                    </div>
                `;
				conversationList.appendChild(listItem);
			});
			highlightActiveConversation();
		} catch (error) {
			console.error('会話リストの取得に失敗しました: ', error);
			document.getElementById('conversationList').innerHTML =
				'<p class="no-conversation">エラー: リストを取得できません</p>';
		}
	}

	// **会話を開く**
	window.openConversation = function (newConversationId) {
		conversationId = newConversationId;
		window.history.pushState({}, '', `index.php?conversation_id=${conversationId}`);
		loadConversation();
		refreshConversationList();
	};

	// **会話履歴を取得**
	async function loadConversation() {
		if (!conversationId) return;

		const response = await fetch(`get_messages.php?conversation_id=${conversationId}`);
		const data = await response.json();

		history.innerHTML = '';
		data.forEach((message) => {
			const messageDiv = document.createElement('div');
			messageDiv.className = message.sender === 'user' ? 'user-message' : 'bot-message';
			messageDiv.textContent = message.message;
			history.appendChild(messageDiv);
		});

		// **会話履歴の最後までスクロール**
		setTimeout(() => {
			scrollToBottom();
			console.log('Loaded conversation and scrolled to bottom'); // デバッグ用ログ
		}, 100);

		if (data.length > 0) {
			isFirstMessage = false;
			logo.classList.add('small');
			inputArea.classList.remove('initial');
			inputArea.classList.add('bottom');
		}

		scrollToBottom(); // **会話履歴を開いた時にスクロール**
	}

	// **タイトルの編集**
	window.editTitle = function (button) {
		const listItem = button.closest('.conversation-item');
		const titleText = listItem.querySelector('.conversation-title-text');
		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.value = titleText.textContent;
		titleInput.className = 'conversation-title-input';

		titleText.style.display = 'none';
		listItem.appendChild(titleInput);
		titleInput.focus();

		titleInput.addEventListener('keypress', function (event) {
			if (event.key === 'Enter') {
				updateTitle(listItem.dataset.conversationId, titleInput.value);
				titleText.textContent = titleInput.value;
				titleText.style.display = 'block';
				listItem.removeChild(titleInput);
			}
		});
	};

	// **タイトルの更新**
	window.updateTitle = async function (conversationId, newTitle) {
		if (newTitle.trim() === '') {
			alert('タイトルは空にできません');
			return;
		}

		const response = await fetch('update_title.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ conversation_id: conversationId, title: newTitle }),
		});

		const data = await response.json();
		if (data.error) {
			alert('エラー: ' + data.error);
		} else {
			refreshConversationList();
		}
	};

	// **削除確認モーダルを表示**
	window.showDeleteModal = function (conversationId, title) {
		selectedConversationId = conversationId;
		deleteMessage.textContent = `「${title}」を削除しますか？`;
		deleteModal.style.display = 'flex';
	};

	// **キャンセルボタンでモーダルを閉じる**
	cancelDeleteBtn.addEventListener('click', function () {
		deleteModal.style.display = 'none';
		selectedConversationId = null;
	});

	// **削除ボタンを押したときの処理**
	confirmDeleteBtn.addEventListener('click', async function () {
		if (!selectedConversationId) return;

		const response = await fetch('delete_conversation.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ conversation_id: selectedConversationId }),
		});

		const data = await response.json();
		if (!data.error) {
			deleteModal.style.display = 'none';
			refreshConversationList();
		} else {
			alert('エラー: ' + data.error);
		}
	});

	refreshConversationList();
	loadConversation();

	// **自動更新（例：5秒ごとに更新）**
	setInterval(refreshConversationList, 5000);
});
