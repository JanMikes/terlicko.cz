import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'modal',
        'messages',
        'input',
        'form',
        'submitButton',
        'loading',
        'error',
        'errorMessage',
        'rateLimit',
        'rateLimitTime',
        'welcome'
    ];

    connect() {
        console.log('Chat controller connected');

        // Restore conversation from localStorage
        this.conversationId = localStorage.getItem('ai_conversation_id');

        // Load existing messages if conversation exists
        if (this.conversationId) {
            this.loadConversationHistory();
        }
    }

    open() {
        console.log('Opening chat modal');

        // Start new conversation if none exists
        if (!this.conversationId) {
            this.startConversation();
        }
    }

    close() {
        console.log('Closing chat modal');
    }

    async startConversation() {
        try {
            const response = await fetch('/chat/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                const data = await response.json();
                if (response.status === 429) {
                    this.showRateLimit(data.retry_after);
                    return;
                }
                throw new Error(data.message || 'Failed to start conversation');
            }

            const data = await response.json();
            this.conversationId = data.conversation_id;
            localStorage.setItem('ai_conversation_id', this.conversationId);

            console.log('Conversation started:', this.conversationId);

            // Hide welcome message
            if (this.hasWelcomeTarget) {
                this.welcomeTarget.classList.add('d-none');
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
            this.showError('Nepodařilo se zahájit konverzaci. Zkuste to prosím znovu.');
        }
    }

    async sendMessage(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();
        if (!message) return;

        // Ensure we have a conversation
        if (!this.conversationId) {
            await this.startConversation();
            if (!this.conversationId) return; // Failed to start
        }

        // Hide welcome
        if (this.hasWelcomeTarget) {
            this.welcomeTarget.classList.add('d-none');
        }

        // Disable form
        this.inputTarget.disabled = true;
        this.submitButtonTarget.disabled = true;

        // Clear input
        this.inputTarget.value = '';

        // Add user message to UI
        this.addMessage('user', message);

        // Show loading
        this.showLoading();
        this.hideError();
        this.hideRateLimit();

        try {
            // Send message with SSE
            const response = await fetch(`/chat/${this.conversationId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ message }),
            });

            if (!response.ok) {
                if (response.status === 429) {
                    const data = await response.json();
                    this.showRateLimit(data.retry_after);
                    this.hideLoading();
                    this.inputTarget.disabled = false;
                    this.submitButtonTarget.disabled = false;
                    return;
                }
                throw new Error('Failed to send message');
            }

            // Handle SSE stream
            await this.handleStream(response.body);

        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Nepodařilo se odeslat zprávu. Zkuste to prosím znovu.');
            this.hideLoading();
        } finally {
            this.inputTarget.disabled = false;
            this.submitButtonTarget.disabled = false;
            this.inputTarget.focus();
        }
    }

    async handleStream(stream) {
        const reader = stream.getReader();
        const decoder = new TextDecoder();

        let assistantMessage = '';
        let sources = [];
        let messageElement = null;
        let currentEventType = null;

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (line.startsWith('event: ')) {
                        currentEventType = line.substring(7).trim();
                        continue;
                    }

                    if (line.startsWith('data: ')) {
                        const data = JSON.parse(line.substring(6));

                        // Handle different event types based on stored event type
                        if (currentEventType === 'sources') {
                            sources = data;
                            console.log('Received sources:', sources);
                        } else if (currentEventType === 'message') {
                            // Hide loading on first chunk
                            if (!messageElement) {
                                this.hideLoading();
                                messageElement = this.addMessage('assistant', '');
                            }

                            // Append content
                            assistantMessage += data.content;
                            this.updateMessage(messageElement, assistantMessage, sources);
                        } else if (currentEventType === 'done') {
                            console.log('Stream complete');
                            this.hideLoading();
                        } else if (currentEventType === 'error') {
                            this.showError(data.error || 'Nastala chyba při generování odpovědi.');
                            this.hideLoading();
                        }

                        // Reset event type after processing
                        currentEventType = null;
                    }
                }
            }
        } finally {
            reader.releaseLock();
        }
    }

    addMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('d-flex', 'align-items-start', 'mb-3');

        if (role === 'user') {
            messageDiv.classList.add('flex-row-reverse');
            messageDiv.innerHTML = `
                <div class="flex-shrink-0 ms-2">
                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-grow-1 me-2">
                    <div class="chat-user-message rounded-3 p-3 shadow-sm">
                        ${this.escapeHtml(content)}
                    </div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="flex-shrink-0 me-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: var(--bs-primary) !important; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-robot" viewBox="0 0 16 16">
                            <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5M3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.6 26.6 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.93.93 0 0 1-.765.935c-.845.147-2.34.346-4.235.346s-3.39-.2-4.235-.346A.93.93 0 0 1 3 9.219zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a25 25 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.55.076 1.232.149 2.02.193a.25.25 0 0 0 .189-.071l.754-.736.847 1.71a.25.25 0 0 0 .404.062l.932-.97a25 25 0 0 0 1.922-.188.25.25 0 0 0-.068-.495c-.538.074-1.207.145-1.98.189a.25.25 0 0 0-.166.076l-.754.785-.842-1.7a.25.25 0 0 0-.182-.135"/>
                            <path d="M8.5 1.866a1 1 0 1 0-1 0V3h-2A4.5 4.5 0 0 0 1 7.5V8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1v-.5A4.5 4.5 0 0 0 10.5 3h-2zM14 7.5V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5A3.5 3.5 0 0 1 5.5 4h5A3.5 3.5 0 0 1 14 7.5"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="chat-assistant-message rounded-3 p-3 shadow-sm">
                        <div class="message-content">${this.escapeHtml(content)}</div>
                    </div>
                </div>
            `;
        }

        this.messagesTarget.appendChild(messageDiv);
        this.scrollToBottom();

        return messageDiv;
    }

    updateMessage(messageElement, content, sources = []) {
        const contentDiv = messageElement.querySelector('.message-content');
        if (!contentDiv) return;

        contentDiv.innerHTML = this.escapeHtml(content);

        // Add sources if provided
        if (sources.length > 0) {
            let citationsDiv = messageElement.querySelector('.citations');
            if (!citationsDiv) {
                citationsDiv = document.createElement('div');
                citationsDiv.classList.add('citations', 'mt-3', 'pt-3', 'border-top');
                contentDiv.parentElement.appendChild(citationsDiv);
            }

            citationsDiv.innerHTML = `
                <div class="small text-muted">
                    <strong>Zdroje:</strong>
                    <ul class="list-unstyled mt-2 mb-0">
                        ${sources.map(source => `
                            <li class="mb-1">
                                <a href="${source.url}" target="_blank" class="text-decoration-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-link-45deg" viewBox="0 0 16 16">
                                        <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/>
                                        <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z"/>
                                    </svg>
                                    ${this.escapeHtml(source.title)}
                                </a>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        this.scrollToBottom();
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('d-none');
            this.scrollToBottom();
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('d-none');
        }
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorMessageTarget.textContent = message;
            this.errorTarget.classList.remove('d-none');
            this.scrollToBottom();
        }
    }

    hideError() {
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('d-none');
        }
    }

    showRateLimit(retryAfter) {
        if (this.hasRateLimitTarget) {
            const now = Date.now() / 1000;
            const seconds = Math.ceil(retryAfter - now);

            if (seconds > 0) {
                const minutes = Math.floor(seconds / 60);
                const secs = seconds % 60;
                const timeString = minutes > 0 ? `${minutes}m ${secs}s` : `${secs}s`;

                this.rateLimitTimeTarget.textContent = timeString;
                this.rateLimitTarget.classList.remove('d-none');
                this.scrollToBottom();
            }
        }
    }

    hideRateLimit() {
        if (this.hasRateLimitTarget) {
            this.rateLimitTarget.classList.add('d-none');
        }
    }

    async endConversation() {
        if (!this.conversationId) return;

        if (!confirm('Opravdu chcete ukončit konverzaci? Historie bude smazána.')) {
            return;
        }

        try {
            const response = await fetch(`/chat/${this.conversationId}/end`, {
                method: 'POST',
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to end conversation');
            }

            // Clear local state
            this.conversationId = null;
            localStorage.removeItem('ai_conversation_id');

            // Clear messages
            this.messagesTarget.innerHTML = '';

            // Show welcome message
            if (this.hasWelcomeTarget) {
                this.welcomeTarget.classList.remove('d-none');
            }

            console.log('Conversation ended');
        } catch (error) {
            console.error('Error ending conversation:', error);
            this.showError('Nepodařilo se ukončit konverzaci.');
        }
    }

    async loadConversationHistory() {
        if (!this.conversationId) return;

        try {
            const response = await fetch(`/chat/${this.conversationId}`, {
                method: 'GET',
                credentials: 'include',
            });

            if (!response.ok) {
                if (response.status === 404) {
                    // Conversation not found, clear local storage
                    this.conversationId = null;
                    localStorage.removeItem('ai_conversation_id');
                    return;
                }
                throw new Error('Failed to load conversation');
            }

            const data = await response.json();

            // Check if conversation is still active
            if (!data.is_active) {
                this.conversationId = null;
                localStorage.removeItem('ai_conversation_id');
                return;
            }

            // Hide welcome message if we have messages
            if (data.messages.length > 0 && this.hasWelcomeTarget) {
                this.welcomeTarget.classList.add('d-none');
            }

            // Render messages
            for (const message of data.messages) {
                const messageElement = this.addMessage(message.role, message.content);

                // Add citations if present
                if (message.role === 'assistant' && message.citations && message.citations.length > 0) {
                    this.updateMessage(messageElement, message.content, message.citations);
                }
            }

            console.log('Conversation history loaded:', data.messages.length, 'messages');
        } catch (error) {
            console.error('Error loading conversation history:', error);
            // Clear invalid conversation
            this.conversationId = null;
            localStorage.removeItem('ai_conversation_id');
        }
    }

    scrollToBottom() {
        const modalBody = this.modalTarget.querySelector('.modal-body');
        if (modalBody) {
            modalBody.scrollTop = modalBody.scrollHeight;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
