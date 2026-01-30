import { Controller } from '@hotwired/stimulus';
import { marked } from 'marked';
import morphdom from 'morphdom';

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
        'welcome',
        'feedback'
    ];

    static values = {
        avatarUrl: String
    };

    connect() {
        console.log('Chat controller connected');

        // Configure marked for GitHub-flavored markdown with line breaks
        marked.use({ gfm: true, breaks: true });

        // Restore conversation from localStorage
        this.conversationId = localStorage.getItem('ai_conversation_id');

        // Load existing messages if conversation exists
        if (this.conversationId) {
            this.loadConversationHistory();
        }

        // Listen for modal show event to handle external triggers
        if (this.hasModalTarget) {
            this.modalTarget.addEventListener('shown.bs.modal', () => {
                this.open();
            });
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

                const data = await response.json();
                if (data.error === 'message_flagged') {
                    this.hideLoading();
                    this.addMessage('assistant', data.message);
                    this.inputTarget.disabled = false;
                    this.submitButtonTarget.disabled = false;
                    return;
                }

                if (data.error === 'moderation_blocked') {
                    this.hideLoading();
                    this.addMessage('assistant', data.message);
                    this.moderationCooldownActive = true;
                    this.startModerationCooldown(data.blocked_until);
                    return;
                }

                if (data.error === 'offtopic_blocked') {
                    this.hideLoading();
                    this.addMessage('assistant', data.message);
                    this.showFeedback();
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
            if (!this.moderationCooldownActive) {
                this.inputTarget.disabled = false;
                this.submitButtonTarget.disabled = false;
                this.inputTarget.focus();
            }
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

                            // Append content (don't show sources yet - wait for completion)
                            assistantMessage += data.content;
                            this.updateMessage(messageElement, assistantMessage, [], false);
                        } else if (currentEventType === 'done') {
                            console.log('Stream complete');
                            this.hideLoading();
                            // Now show sources if appropriate (final update)
                            if (messageElement) {
                                this.updateMessage(messageElement, assistantMessage, sources, true);
                            }
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
        messageDiv.classList.add('chat-message');

        if (role === 'user') {
            messageDiv.classList.add('chat-message-user');
            messageDiv.innerHTML = `
                <div class="chat-avatar chat-avatar-user">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#9ca3af" viewBox="0 0 16 16">
                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                    </svg>
                </div>
                <div class="chat-bubble chat-bubble-user">
                    ${this.escapeHtml(content)}
                </div>
            `;
        } else {
            messageDiv.classList.add('chat-message-assistant');
            messageDiv.innerHTML = `
                <div class="chat-avatar">
                    <img src="${this.avatarUrlValue}" alt="Terka">
                </div>
                <div class="chat-bubble chat-bubble-assistant">
                    <div class="message-content">${content ? this.formatContent(content) : ''}</div>
                </div>
            `;
        }

        this.messagesTarget.appendChild(messageDiv);
        this.scrollToBottom();

        return messageDiv;
    }

    updateMessage(messageElement, content, sources = {}, isFinal = false) {
        const contentDiv = messageElement.querySelector('.message-content');
        if (!contentDiv) return;

        // Normalize sources for citation links
        const normalizedSources = this.normalizeSources(sources);
        const allSources = [...normalizedSources.initial, ...normalizedSources.expanded];

        // Format content with markdown and inline citations
        const newHtml = this.formatContent(content, allSources);

        // Use morphdom for efficient DOM updates without flickering
        const tempDiv = document.createElement('div');
        tempDiv.className = 'message-content';
        tempDiv.innerHTML = newHtml;
        morphdom(contentDiv, tempDiv);

        // Extract sources for footer section
        const { initial, expanded, hasMore } = normalizedSources;

        // Only show sources when response is final and AI actually used them
        // Don't show sources if AI says it doesn't have the information
        if (isFinal && initial.length > 0 && this.shouldShowSources(content)) {
            let citationsDiv = messageElement.querySelector('.chat-citations');
            if (!citationsDiv) {
                citationsDiv = document.createElement('div');
                citationsDiv.classList.add('chat-citations');
                contentDiv.parentElement.appendChild(citationsDiv);
            }

            const expandedId = `expanded-sources-${Date.now()}`;

            citationsDiv.innerHTML = `
                <span class="chat-citations-label">Zdroje:</span>
                <ul class="list-unstyled mt-2 mb-0">
                    ${initial.map(source => this.renderSourceItem(source)).join('')}
                </ul>
                ${hasMore ? `
                    <div class="expanded-sources collapse" id="${expandedId}">
                        <ul class="list-unstyled mb-0">
                            ${expanded.map(source => this.renderSourceItem(source)).join('')}
                        </ul>
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-2 show-more-sources" data-bs-toggle="collapse" data-bs-target="#${expandedId}">
                        <span class="show-more-text">Zobrazit další zdroje (${expanded.length})</span>
                        <span class="show-less-text d-none">Skrýt další zdroje</span>
                    </button>
                ` : ''}
            `;

            // Add toggle handler for show more/less text
            if (hasMore) {
                const expandedEl = citationsDiv.querySelector(`#${expandedId}`);
                const button = citationsDiv.querySelector('.show-more-sources');
                if (expandedEl && button) {
                    expandedEl.addEventListener('shown.bs.collapse', () => {
                        button.querySelector('.show-more-text').classList.add('d-none');
                        button.querySelector('.show-less-text').classList.remove('d-none');
                    });
                    expandedEl.addEventListener('hidden.bs.collapse', () => {
                        button.querySelector('.show-more-text').classList.remove('d-none');
                        button.querySelector('.show-less-text').classList.add('d-none');
                    });
                }
            }
        }

        this.scrollToBottom();
    }

    /**
     * Render a single source item
     */
    renderSourceItem(source) {
        const icon = source.type === 'webpage'
            ? `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7 7 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-1.834a7 7 0 0 0 .656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-1.834a13.7 13.7 0 0 1-.312 2.5zm2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z"/>
               </svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-file-earmark-pdf" viewBox="0 0 16 16">
                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                <path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 0 1.062-2.227 7.3 7.3 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a11 11 0 0 0 .98 1.686 5.8 5.8 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.86.86 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.7 5.7 0 0 1-.911-.95 11.7 11.7 0 0 0-1.997.406 11.3 11.3 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.8.8 0 0 1-.58.029"/>
               </svg>`;

        return `
            <li class="mb-1">
                <a href="${source.url}" target="_blank" class="text-decoration-none">
                    ${icon}
                    ${this.escapeHtml(source.title)}
                </a>
            </li>
        `;
    }

    /**
     * Normalize sources to new format (handles both old array and new object format)
     */
    normalizeSources(sources) {
        // New format: { initial: [], expanded: [], hasMore: bool }
        if (sources && typeof sources === 'object' && 'initial' in sources) {
            return sources;
        }

        // Old format: array of sources - convert to new format
        if (Array.isArray(sources)) {
            return {
                initial: sources,
                expanded: [],
                hasMore: false
            };
        }

        // Empty/invalid
        return {
            initial: [],
            expanded: [],
            hasMore: false
        };
    }

    /**
     * Check if sources should be shown based on AI response content
     * Returns false if AI indicates it cannot/won't answer the question
     */
    shouldShowSources(content) {
        const lowerContent = content.toLowerCase();
        const noInfoPhrases = [
            // AI doesn't have the information
            'nemám k dispozici',
            'nemám informaci',
            'tuto informaci bohužel',
            'informaci nemám',
            'nejsem schopen',
            'nemohu odpovědět',
            'nemám dostatek informací',
            'nemám žádné informace',
            // AI declines to answer (off-topic questions)
            'mohu odpovídat pouze',
            'pouze na otázky týkající se',
            'nespadá do mé působnosti',
            'není v mé kompetenci',
            'tato otázka se netýká'
        ];

        return !noInfoPhrases.some(phrase => lowerContent.includes(phrase));
    }

    /**
     * Format content with markdown parsing and inline citations
     */
    formatContent(content, sources = []) {
        // Parse markdown to HTML
        let html = marked.parse(content);
        // Convert inline citations [[n]] to clickable links
        html = this.convertInlineCitations(html, sources);
        return html;
    }

    /**
     * Convert [text][[n]] and [[n]] citation markers to clickable links
     */
    convertInlineCitations(html, sources) {
        const allSources = this.flattenSources(sources);

        // First, handle [text][[n]] format - linked text with citation
        html = html.replace(/\[([^\]]+)\]\[\[(\d+)\]\]/g, (match, text, num) => {
            const index = parseInt(num, 10) - 1;
            const source = allSources[index];
            if (source) {
                return `<a href="${source.url}" target="_blank" class="citation-text-link" title="${this.escapeHtml(source.title)}">${text}<sup>[${num}]</sup></a>`;
            }
            return text;
        });

        // Then, handle standalone [[n]] format - just superscript link
        html = html.replace(/\[\[(\d+)\]\]/g, (match, num) => {
            const index = parseInt(num, 10) - 1;
            const source = allSources[index];
            if (source) {
                return `<sup><a href="${source.url}" target="_blank" class="citation-link" title="${this.escapeHtml(source.title)}">[${num}]</a></sup>`;
            }
            return match;
        });

        return html;
    }

    /**
     * Flatten sources from normalized format to simple array
     */
    flattenSources(sources) {
        const normalized = this.normalizeSources(sources);
        return [...normalized.initial, ...normalized.expanded];
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

    showFeedback() {
        if (this.hasFeedbackTarget) {
            this.feedbackTarget.classList.remove('d-none');
        }
    }

    hideFeedback() {
        if (this.hasFeedbackTarget) {
            this.feedbackTarget.classList.add('d-none');
        }
    }

    startModerationCooldown(blockedUntilTimestamp) {
        this.inputTarget.disabled = true;
        this.submitButtonTarget.disabled = true;
        this.inputTarget.placeholder = 'Počkejte prosím...';

        const updateCooldown = () => {
            const now = Math.floor(Date.now() / 1000);
            const remaining = blockedUntilTimestamp - now;

            if (remaining <= 0) {
                this.moderationCooldownActive = false;
                this.inputTarget.disabled = false;
                this.submitButtonTarget.disabled = false;
                this.inputTarget.placeholder = 'Napište svou otázku...';
                this.inputTarget.focus();
                return;
            }

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            const timeString = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            this.inputTarget.placeholder = `Pauza – můžete psát za ${timeString}`;

            setTimeout(updateCooldown, 1000);
        };

        updateCooldown();
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

                // Add citations if present (for assistant messages, check if should show sources)
                if (message.role === 'assistant') {
                    const sourcesToShow = (message.citations && message.citations.length > 0 && this.shouldShowSources(message.content))
                        ? message.citations
                        : [];
                    this.updateMessage(messageElement, message.content, sourcesToShow, true);
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
        const modalBody = this.modalTarget.querySelector('.chat-modal-body');
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
