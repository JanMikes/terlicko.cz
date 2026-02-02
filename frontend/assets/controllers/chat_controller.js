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
        'feedback',
        'conversationsContainer',
        'conversationsList',
        'showMoreBtn',
        'floatingButtonAvatar',
        'headerAvatar',
        'welcomeAvatar',
        'loadingAvatar',
        'configButton',
        'avatarSelection',
        'chatContent',
        'avatarOption',
        'messageFeedback',
        'newConversationBtn'
    ];

    static values = {
        avatarUrls: Object,
        defaultAvatarKey: { type: String, default: '1' }
    };

    connect() {
        console.log('Chat controller connected');

        // Configure marked for GitHub-flavored markdown with line breaks
        marked.use({ gfm: true, breaks: true });

        // Load avatar choice from localStorage
        this.loadAvatarChoice();

        // Migrate from old localStorage format if needed
        this.migrateOldStorage();

        // Load conversations from storage
        this.loadConversationsFromStorage();

        // Listen for modal show event to handle external triggers
        if (this.hasModalTarget) {
            this.modalTarget.addEventListener('shown.bs.modal', () => {
                this.open();
            });
        }
    }

    /**
     * Migrate from old single-conversation storage to new multi-conversation format
     */
    migrateOldStorage() {
        const oldConversationId = localStorage.getItem('ai_conversation_id');
        if (oldConversationId) {
            // Check if we already have the new format
            const existingData = localStorage.getItem('ai_conversations_data');
            if (!existingData) {
                // Create new format with old conversation
                const newData = {
                    conversations: [{
                        id: oldConversationId,
                        title: null,
                        startedAt: new Date().toISOString()
                    }],
                    activeId: oldConversationId
                };
                localStorage.setItem('ai_conversations_data', JSON.stringify(newData));
            }
            // Remove old format
            localStorage.removeItem('ai_conversation_id');
        }
    }

    /**
     * Get conversations data from localStorage
     */
    getStoredConversations() {
        try {
            const data = localStorage.getItem('ai_conversations_data');
            if (data) {
                return JSON.parse(data);
            }
        } catch (e) {
            console.error('Error parsing conversations data:', e);
        }
        return { conversations: [], activeId: null };
    }

    /**
     * Save conversations data to localStorage
     */
    saveConversationsToStorage(data) {
        localStorage.setItem('ai_conversations_data', JSON.stringify(data));
    }

    /**
     * Load conversations from storage and set active conversation
     */
    loadConversationsFromStorage() {
        const data = this.getStoredConversations();
        this.conversations = data.conversations;
        this.conversationId = data.activeId;

        // Don't load conversation history here - wait until modal is opened
        // This prevents API calls that might fail before user interacts

        // Render conversation chips
        this.renderConversationChips();
    }

    /**
     * Add a new conversation to storage
     */
    addConversationToStorage(id, title = null) {
        const data = this.getStoredConversations();

        // Check if conversation already exists
        const exists = data.conversations.some(c => c.id === id);
        if (!exists) {
            data.conversations.unshift({
                id: id,
                title: title,
                startedAt: new Date().toISOString()
            });
        }

        data.activeId = id;
        this.saveConversationsToStorage(data);
        this.conversations = data.conversations;
        this.conversationId = id;
    }

    /**
     * Update conversation title in storage
     */
    updateConversationTitle(conversationId, title) {
        const data = this.getStoredConversations();
        const conv = data.conversations.find(c => c.id === conversationId);
        if (conv) {
            conv.title = title;
            this.saveConversationsToStorage(data);
            this.conversations = data.conversations;
            this.renderConversationChips();
        }
    }

    /**
     * Render conversation chips in header
     */
    renderConversationChips() {
        if (!this.hasConversationsListTarget) return;

        // Find the new conversation button
        const newBtn = this.conversationsListTarget.querySelector('.chat-new-btn');

        // Remove existing chips (but keep the new button)
        const existingChips = this.conversationsListTarget.querySelectorAll('.chat-conversation-chip');
        existingChips.forEach(chip => chip.remove());

        // Show/hide the new conversation button based on whether we have conversations
        if (this.hasNewConversationBtnTarget) {
            if (this.conversations && this.conversations.length > 0) {
                this.newConversationBtnTarget.classList.remove('d-none');
            } else {
                this.newConversationBtnTarget.classList.add('d-none');
            }
        }

        // Add chips for each conversation
        this.conversations.forEach(conv => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chat-conversation-chip';
            if (conv.id === this.conversationId) {
                chip.classList.add('active');
            }
            chip.textContent = conv.title || 'Konverzace';
            chip.dataset.conversationId = conv.id;
            chip.dataset.action = 'click->chat#switchConversation';

            // Insert before the new button
            this.conversationsListTarget.insertBefore(chip, newBtn);
        });

        // Check if we need to show "show more" button
        this.checkShowMoreVisibility();
    }

    /**
     * Check if "show more" button should be visible
     */
    checkShowMoreVisibility() {
        if (!this.hasShowMoreBtnTarget || !this.hasConversationsListTarget) return;

        // Check if content overflows (more than 2 lines)
        const list = this.conversationsListTarget;
        const isOverflowing = list.scrollHeight > 60; // Slightly more than max-height

        if (isOverflowing && !list.classList.contains('expanded')) {
            this.showMoreBtnTarget.classList.remove('d-none');
        } else if (!isOverflowing) {
            this.showMoreBtnTarget.classList.add('d-none');
        }
    }

    /**
     * Toggle show more/less for conversation list
     */
    toggleShowMore() {
        if (!this.hasConversationsListTarget || !this.hasShowMoreBtnTarget) return;

        const list = this.conversationsListTarget;
        const isExpanded = list.classList.toggle('expanded');

        const moreText = this.showMoreBtnTarget.querySelector('.show-more-text');
        const lessText = this.showMoreBtnTarget.querySelector('.show-less-text');

        if (isExpanded) {
            moreText.classList.add('d-none');
            lessText.classList.remove('d-none');
        } else {
            moreText.classList.remove('d-none');
            lessText.classList.add('d-none');
        }
    }

    /**
     * Switch to a different conversation
     */
    async switchConversation(event) {
        const newId = event.currentTarget.dataset.conversationId;
        if (newId === this.conversationId) return;

        // Update active conversation
        const data = this.getStoredConversations();
        data.activeId = newId;
        this.saveConversationsToStorage(data);
        this.conversationId = newId;

        // Clear current messages
        this.messagesTarget.innerHTML = '';

        // Show welcome message temporarily
        if (this.hasWelcomeTarget) {
            this.welcomeTarget.classList.remove('d-none');
        }

        // Load the conversation history
        await this.loadConversationHistory();
        this.loadedConversationId = newId;

        // Re-render chips to update active state
        this.renderConversationChips();
    }

    /**
     * Start a new conversation (keeps previous conversations active for switching)
     */
    newConversation() {
        // Reset loaded conversation tracking
        this.loadedConversationId = null;

        // Don't end the previous conversation - user can switch back to it

        // Clear local state
        this.conversationId = null;

        // Clear messages
        this.messagesTarget.innerHTML = '';

        // Show welcome message
        if (this.hasWelcomeTarget) {
            this.welcomeTarget.classList.remove('d-none');
        }

        // Update storage - set activeId to null but keep conversations list
        const data = this.getStoredConversations();
        data.activeId = null;
        this.saveConversationsToStorage(data);

        // Re-render chips (none will be active)
        this.renderConversationChips();
    }

    /**
     * Sync conversations with backend on modal open
     */
    async syncConversations() {
        try {
            const response = await fetch('/chat/conversations', {
                method: 'GET',
                credentials: 'include',
            });

            if (!response.ok) {
                // Don't modify local data on error
                return;
            }

            const serverConversations = await response.json();

            // If server returns empty and we have local data, keep local data
            // (this can happen if guest cookie changed)
            if (serverConversations.length === 0 && this.conversations && this.conversations.length > 0) {
                console.log('Server returned no conversations, keeping local data');
                return;
            }

            // Merge with local data (server is source of truth for titles)
            const data = this.getStoredConversations();

            // Create a map of server conversations
            const serverMap = new Map(serverConversations.map(c => [c.id, c]));

            // Update local conversations with server data (only update titles, don't remove)
            data.conversations = data.conversations.map(local => {
                const server = serverMap.get(local.id);
                if (server) {
                    return {
                        ...local,
                        title: server.title || local.title,
                        startedAt: server.started_at || local.startedAt
                    };
                }
                // Keep local conversation even if not on server
                return local;
            });

            // Add any new conversations from server that we don't have locally
            serverConversations.forEach(server => {
                const exists = data.conversations.some(c => c.id === server.id);
                if (!exists && server.is_active) {
                    data.conversations.push({
                        id: server.id,
                        title: server.title,
                        startedAt: server.started_at
                    });
                }
            });

            // Sort by startedAt descending
            data.conversations.sort((a, b) => new Date(b.startedAt) - new Date(a.startedAt));

            this.saveConversationsToStorage(data);
            this.conversations = data.conversations;

            // Re-render chips
            this.renderConversationChips();
        } catch (error) {
            console.error('Error syncing conversations:', error);
            // Don't modify local data on error
        }
    }

    open() {
        console.log('Opening chat modal');

        // Load conversation history if we have an active conversation that hasn't been loaded yet
        // This is done here (not in connect) to ensure cookie is properly sent
        if (this.conversationId && this.loadedConversationId !== this.conversationId) {
            this.loadConversationHistory();
            this.loadedConversationId = this.conversationId;
        }

        // Only sync conversations if we already have some locally
        // (which means we have a guest ID cookie)
        if (this.conversations && this.conversations.length > 0) {
            this.syncConversations();
        }

        // Don't start conversation automatically - wait for user to send first message
        // The conversation will be started in sendMessage() when needed
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

            // Add to storage
            this.addConversationToStorage(this.conversationId, null);

            // Render chips
            this.renderConversationChips();

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
                        } else if (currentEventType === 'title_update') {
                            // Handle title update from server
                            console.log('Received title update:', data);
                            if (data.title && data.conversation_id) {
                                this.updateConversationTitle(data.conversation_id, data.title);
                            }
                        } else if (currentEventType === 'message_saved') {
                            // Store the message ID on the element for feedback
                            if (messageElement && data.message_id) {
                                messageElement.dataset.messageId = data.message_id;
                                console.log('Message ID stored:', data.message_id);
                            }
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

    addMessage(role, content, messageId = null) {
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
            if (messageId) {
                messageDiv.dataset.messageId = messageId;
            }
            messageDiv.innerHTML = `
                <div class="chat-avatar">
                    <img src="${this.getCurrentAvatarUrl()}" alt="Terka">
                </div>
                <div class="chat-bubble chat-bubble-assistant">
                    <button type="button" class="chat-feedback-btn" data-action="click->chat#showFeedbackForm" title="Nahlásit problém">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12 12 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A20 20 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a20 20 0 0 0 1.349-.476l.019-.007.004-.002h.001"/>
                        </svg>
                    </button>
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
                // Append to message wrapper (outside the bubble)
                messageElement.appendChild(citationsDiv);
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

    async loadConversationHistory() {
        if (!this.conversationId) return;

        try {
            const response = await fetch(`/chat/${this.conversationId}`, {
                method: 'GET',
                credentials: 'include',
            });

            if (!response.ok) {
                if (response.status === 404) {
                    // Conversation not found, remove from storage
                    const data = this.getStoredConversations();
                    data.conversations = data.conversations.filter(c => c.id !== this.conversationId);
                    if (data.activeId === this.conversationId) {
                        data.activeId = null;
                    }
                    this.saveConversationsToStorage(data);
                    this.conversationId = null;
                    this.conversations = data.conversations;
                    this.renderConversationChips();
                    return;
                }
                throw new Error('Failed to load conversation');
            }

            const data = await response.json();

            // Check if conversation is still active
            if (!data.is_active) {
                // Remove from local storage
                const storageData = this.getStoredConversations();
                storageData.conversations = storageData.conversations.filter(c => c.id !== this.conversationId);
                if (storageData.activeId === this.conversationId) {
                    storageData.activeId = null;
                }
                this.saveConversationsToStorage(storageData);
                this.conversationId = null;
                this.conversations = storageData.conversations;
                this.renderConversationChips();
                return;
            }

            // Update title if it came from server
            if (data.title) {
                this.updateConversationTitle(this.conversationId, data.title);
            }

            // Hide welcome message if we have messages
            if (data.messages.length > 0 && this.hasWelcomeTarget) {
                this.welcomeTarget.classList.add('d-none');
            }

            // Render messages
            for (const message of data.messages) {
                const messageElement = this.addMessage(message.role, message.content, message.id);

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
            // Don't remove conversation on generic errors (network issues, etc.)
            // Just show an error message to the user
            this.showError('Nepodařilo se načíst historii konverzace.');
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

    /**
     * Load avatar choice from localStorage and apply it
     */
    loadAvatarChoice() {
        const savedKey = localStorage.getItem('ai_avatar_choice') || this.defaultAvatarKeyValue;
        this.currentAvatarKey = savedKey;
        this.applyAvatar(savedKey);
    }

    /**
     * Apply avatar to all avatar targets
     */
    applyAvatar(key) {
        if (!this.hasAvatarUrlsValue || !this.avatarUrlsValue[key]) {
            return;
        }

        const urls = this.avatarUrlsValue[key];

        // Update all avatar image sources
        if (this.hasFloatingButtonAvatarTarget) {
            this.floatingButtonAvatarTarget.src = urls.icon;
        }
        if (this.hasHeaderAvatarTarget) {
            this.headerAvatarTarget.src = urls.icon;
        }
        if (this.hasWelcomeAvatarTarget) {
            this.welcomeAvatarTarget.src = urls.icon;
        }
        if (this.hasLoadingAvatarTarget) {
            this.loadingAvatarTarget.src = urls.icon;
        }

        // Update selected state on avatar options
        if (this.hasAvatarOptionTarget) {
            this.avatarOptionTargets.forEach(option => {
                if (option.dataset.avatarKey === key) {
                    option.classList.add('selected');
                } else {
                    option.classList.remove('selected');
                }
            });
        }

        this.currentAvatarKey = key;
    }

    /**
     * Get the current avatar icon URL
     */
    getCurrentAvatarUrl() {
        if (this.hasAvatarUrlsValue && this.avatarUrlsValue[this.currentAvatarKey]) {
            return this.avatarUrlsValue[this.currentAvatarKey].icon;
        }
        return '';
    }

    /**
     * Show avatar selection view
     */
    showAvatarSelection() {
        if (this.hasAvatarSelectionTarget && this.hasChatContentTarget) {
            this.chatContentTarget.classList.add('d-none');
            this.avatarSelectionTarget.classList.remove('d-none');
        }
    }

    /**
     * Hide avatar selection view (back to chat)
     */
    hideAvatarSelection() {
        if (this.hasAvatarSelectionTarget && this.hasChatContentTarget) {
            this.avatarSelectionTarget.classList.add('d-none');
            this.chatContentTarget.classList.remove('d-none');
        }
    }

    /**
     * Handle avatar selection
     */
    selectAvatar(event) {
        const button = event.currentTarget;
        const key = button.dataset.avatarKey;

        if (!key) return;

        // Save to localStorage
        localStorage.setItem('ai_avatar_choice', key);

        // Apply avatar
        this.applyAvatar(key);

        // Dispatch custom event for homepage sync
        this.dispatchAvatarChange(key);

        // Return to chat view
        this.hideAvatarSelection();
    }

    /**
     * Dispatch custom event when avatar changes (for homepage sync)
     */
    dispatchAvatarChange(key) {
        window.dispatchEvent(new CustomEvent('ai-avatar-changed', {
            detail: { key: key }
        }));
    }

    /**
     * Show feedback form for an assistant message
     */
    showFeedbackForm(event) {
        const button = event.currentTarget;
        const bubble = button.closest('.chat-bubble-assistant');
        const messageDiv = button.closest('.chat-message-assistant');

        // If already has feedback, do nothing
        if (button.classList.contains('feedback-given')) {
            return;
        }

        // Close any existing feedback forms
        this.closeAllFeedbackForms();

        // Create feedback form
        const form = document.createElement('div');
        form.className = 'chat-feedback-form';
        form.innerHTML = `
            <label>Pomozte nám zlepšit odpovědi</label>
            <textarea placeholder="Popište, co bylo špatně..." data-chat-target="feedbackTextarea"></textarea>
            <div class="chat-feedback-actions">
                <button type="button" class="chat-feedback-cancel" data-action="click->chat#hideFeedbackForm">Zrušit</button>
                <button type="button" class="chat-feedback-submit" data-action="click->chat#submitFeedback">Odeslat</button>
            </div>
        `;

        // Store reference to the message for submission
        form.dataset.messageId = messageDiv.dataset.messageId || '';

        bubble.appendChild(form);
        form.querySelector('textarea').focus();
        this.scrollToBottom();
    }

    /**
     * Hide feedback form
     */
    hideFeedbackForm(event) {
        const form = event.currentTarget.closest('.chat-feedback-form');
        if (form) {
            form.remove();
        }
    }

    /**
     * Close all open feedback forms
     */
    closeAllFeedbackForms() {
        const forms = this.messagesTarget.querySelectorAll('.chat-feedback-form');
        forms.forEach(form => form.remove());
    }

    /**
     * Submit feedback for an assistant message
     */
    async submitFeedback(event) {
        const form = event.currentTarget.closest('.chat-feedback-form');
        const textarea = form.querySelector('textarea');
        const submitBtn = form.querySelector('.chat-feedback-submit');
        const messageId = form.dataset.messageId;
        const feedbackText = textarea.value.trim();

        if (!feedbackText) {
            textarea.focus();
            return;
        }

        if (!messageId) {
            console.error('No message ID found for feedback');
            return;
        }

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Odesílání...';

        try {
            const response = await fetch(`/chat/messages/${messageId}/feedback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ feedback: feedbackText }),
            });

            if (!response.ok) {
                throw new Error('Failed to submit feedback');
            }

            // Replace form with thank you message
            const thanksDiv = document.createElement('div');
            thanksDiv.className = 'chat-feedback-thanks mt-2';
            thanksDiv.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
                Děkujeme za zpětnou vazbu!
            `;
            form.replaceWith(thanksDiv);

            // Update the feedback button to show checkmark
            const bubble = thanksDiv.closest('.chat-bubble-assistant');
            const feedbackBtn = bubble.querySelector('.chat-feedback-btn');
            if (feedbackBtn) {
                feedbackBtn.classList.add('feedback-given');
                feedbackBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                `;
                feedbackBtn.title = 'Zpětná vazba odeslána';
            }

        } catch (error) {
            console.error('Error submitting feedback:', error);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Odeslat';
            // Show error inline
            const errorSpan = document.createElement('span');
            errorSpan.className = 'text-danger small';
            errorSpan.textContent = ' Chyba při odesílání';
            form.querySelector('.chat-feedback-actions').appendChild(errorSpan);
        }
    }
}
