// Chatbot AI assistant functionality
let isChatbotLoading = false;

function openChatbot() {
  const widget = document.getElementById('chatbotWidget');
  widget.style.display = 'block';
  loadChatHistory();
}

function closeChatbot() {
  const widget = document.getElementById('chatbotWidget');
  widget.style.display = 'none';
}

function handleChatKeypress(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

async function loadChatHistory() {
  try {
    const response = await fetch('../chatbot/get_history.php');
    const result = await response.json();

    if (!result.success) return;

    const messagesContainer = document.getElementById('chatbotMessages');

    // Preserve only the welcome message
    const welcome = messagesContainer.firstElementChild;
    messagesContainer.innerHTML = '';
    if (welcome) messagesContainer.appendChild(welcome);

    result.messages.forEach(msg => {
      addMessageToChat(msg.message, msg.sender, false);
    });

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  } catch (error) {
    console.error('Error loading chat history:', error);
  }
}

async function sendMessage() {
  removeTypingIndicator();

  const input = document.getElementById('chatInput');
  const message = input.value.trim();

  if (!message || isChatbotLoading) return;

  addMessageToChat(message, 'user');
  input.value = '';
  showTypingIndicator();
  isChatbotLoading = true;

  try {
    const response = await fetch('../chatbot/chat_handler.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: message
      })
    });

    const text = await response.text();

    // Try to parse JSON
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('Response is not JSON:', text);
      removeTypingIndicator();
      isChatbotLoading = false;
      addMessageToChat('Error: Server returned invalid response. Please check browser console for details.', 'bot');
      return;
    }

    removeTypingIndicator();
    isChatbotLoading = false;

    if (result.success) {
      addMessageToChat(result.reply, 'bot');
    } else {
      addMessageToChat('Error: ' + (result.error || 'Unknown error occurred'), 'bot');
    }
  } catch (error) {
    console.error('Chatbot error:', error);
    removeTypingIndicator();
    isChatbotLoading = false;
    addMessageToChat('Network error. Please try again.', 'bot');
  }
}

function addMessageToChat(message, sender, scrollToBottom = true) {
  const messagesContainer = document.getElementById('chatbotMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = sender === 'user' ? 'user-message d-flex gap-3 mb-4 flex-row-reverse' : 'bot-message d-flex gap-3 mb-4';
  const avatar = sender === 'user' ? '👤' : '🤖';

  messageDiv.innerHTML = `
    <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: #1E88E5 !important; color: white; font-size: 0.9rem;">${avatar}</div>
    <div class="message-content ${sender === 'user' ? 'text-white' : 'bg-white'} p-2 ${sender === 'user' ? '' : 'rounded-3'} small" style="${sender === 'user' ? 'background: #1E88E5 !important; border-radius: 25px;' : ''} max-width: 75%; word-wrap: break-word;">${escapeHtml(message)}</div>
  `;

  messagesContainer.appendChild(messageDiv);

  if (scrollToBottom) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function showTypingIndicator() {
  const messagesContainer = document.getElementById('chatbotMessages');
  const typingDiv = document.createElement('div');
  typingDiv.className = 'bot-message typing-indicator-container d-flex gap-3 mb-4';
  typingDiv.id = 'typingIndicator';
  typingDiv.innerHTML = `
    <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: #1E88E5 !important; color: white; font-size: 0.9rem;">🤖</div>
    <div class="typing-indicator d-flex gap-1 p-2 bg-light rounded-3">
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite;"></div>
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite 0.2s;"></div>
      <div class="typing-dot rounded-circle bg-secondary" style="width: 6px; height: 6px; animation: typing 1.4s infinite 0.4s;"></div>
    </div>
  `;
  messagesContainer.appendChild(typingDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function removeTypingIndicator() {
  const indicator = document.getElementById('typingIndicator');
  if (indicator) {
    indicator.remove();
  }
}

async function clearChatHistory() {
  if (!confirm('Are you sure you want to clear your chat history?')) return;

  try {
    const response = await fetch('../chatbot/clear_chat.php', {
      method: 'POST'
    });
    const text = await response.text();

    // Try to parse JSON
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('Response is not JSON:', text);
      alert('Error clearing chat. Check browser console.');
      return;
    }

    if (result.success) {
      const messagesContainer = document.getElementById('chatbotMessages');
      messagesContainer.innerHTML = `
        <div class="bot-message d-flex gap-2 mb-3">
          <div class="message-avatar rounded-circle d-flex align-items-center justify-content-center" style="flex-shrink: 0; width: 30px; height: 30px; background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%); color: white; font-size: 0.9rem;">🤖</div>
          <div class="message-content bg-white p-2 rounded-3 small shadow-sm">
            Hello! I'm your Electripid assistant powered by AI. I can help you with:
            <br>• Energy consumption analysis
            <br>• Money-saving tips
            <br>• Appliance recommendations
            <br>• Bill estimates
            <br><br>How can I help you today?
          </div>
        </div>
      `;
    } else {
      alert('Error: ' + (result.error || 'Failed to clear chat'));
    }
  } catch (error) {
    console.error('Error clearing chat:', error);
    alert('Network error while clearing chat');
  }
}