import { loadDotEnv } from './load-dotenv';

/**
 * Default DDEV Mailpit endpoint.
 *
 * DDEV also exposes Mailpit over HTTPS on `https://cart.ddev.site:8026`, but that
 * certificate is signed by the local mkcert root, which Node's fetch does not
 * trust. Browsers work, Node does not, so the plain HTTP port is the default.
 * Override with `MAILPIT_URL` when Mailpit lives somewhere else.
 */
export const DEFAULT_MAILPIT_URL = 'http://cart.ddev.site:8025';

const DEFAULT_POLL_INTERVAL_MS = 500;
const DEFAULT_WAIT_TIMEOUT_MS = 30_000;
const DEFAULT_QUIET_MS = 5_000;
const DEFAULT_REQUEST_TIMEOUT_MS = 10_000;
const LIST_LIMIT = 200;

export interface MailpitAddress {
  Name: string;
  Address: string;
}

export interface MailpitMessageSummary {
  ID: string;
  MessageID: string;
  Read: boolean;
  From: MailpitAddress | null;
  To: MailpitAddress[] | null;
  Cc: MailpitAddress[] | null;
  Bcc: MailpitAddress[] | null;
  ReplyTo: MailpitAddress[] | null;
  Subject: string;
  Created: string;
  Tags: string[];
  Size: number;
  /** Attachment count on the list endpoint, not the attachment records. */
  Attachments: number;
  Snippet: string;
}

export interface MailpitListResponse {
  total: number;
  unread: number;
  count: number;
  messages_count: number;
  messages_unread: number;
  start: number;
  tags: string[];
  messages: MailpitMessageSummary[];
}

export interface MailpitAttachment {
  PartID: string;
  FileName: string;
  ContentType: string;
  ContentID: string;
  Size: number;
}

/** `GET /api/v1/message/{id}` — the full record, where `Attachments` is a list. */
export interface MailpitMessage {
  ID: string;
  MessageID: string;
  From: MailpitAddress | null;
  To: MailpitAddress[] | null;
  Cc: MailpitAddress[] | null;
  Bcc: MailpitAddress[] | null;
  ReplyTo: MailpitAddress[] | null;
  ReturnPath: string;
  Subject: string;
  Date: string;
  Tags: string[];
  Text: string;
  HTML: string;
  Size: number;
  Inline: MailpitAttachment[];
  Attachments: MailpitAttachment[];
}

export interface MailpitMatch {
  /** Recipient address, matched case-insensitively against To, Cc and Bcc. */
  to?: string;
  /** Substring of the subject, matched case-insensitively. */
  subject?: string;
}

export interface MailpitWaitOptions {
  timeoutMs?: number;
  intervalMs?: number;
}

export interface MailpitQuietOptions {
  quietMs?: number;
  intervalMs?: number;
}

/**
 * Thin Mailpit REST client for the email settings gate specs.
 *
 * Isolate every assertion by calling `deleteAll()` immediately before the
 * trigger under test.
 */
export class Mailpit {
  private readonly baseUrl: string;

  private constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  static fromEnv(): Mailpit {
    loadDotEnv();

    const raw = process.env.MAILPIT_URL;
    const value = raw !== undefined && raw !== '' ? raw : DEFAULT_MAILPIT_URL;

    return new Mailpit(value.replace(/\/$/, ''));
  }

  static create(baseUrl: string): Mailpit {
    return new Mailpit(baseUrl.replace(/\/$/, ''));
  }

  url(): string {
    return this.baseUrl;
  }

  /**
   * Empties the mailbox. Call before every trigger so assertions are isolated.
   *
   * Mailpit answers this one with the plain-text body `ok`, not JSON.
   */
  async deleteAll(): Promise<void> {
    await this.request('DELETE', '/api/v1/messages');
  }

  /**
   * Cheap liveness probe used to decide whether the @email suite can run at all.
   *
   * Never throws: callers need a boolean to branch on, not an exception. Uses a short
   * timeout so a dev without Mailpit is not left waiting on the default request budget.
   */
  async isReachable(timeoutMs = 3000): Promise<boolean> {
    try {
      const response = await fetch(`${this.baseUrl}/api/v1/messages?limit=1`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(timeoutMs),
      });

      return response.ok;
    } catch {
      return false;
    }
  }

  /** Raw Mailpit search, e.g. `to:"someone@example.invalid"`. */
  async search(query: string, limit: number = LIST_LIMIT): Promise<MailpitMessageSummary[]> {
    const path = `/api/v1/search?query=${encodeURIComponent(query)}&limit=${limit}`;
    const body = await this.requestJson<MailpitListResponse>('GET', path);

    return body.messages ?? [];
  }

  /** Most recent messages, newest first. */
  async list(limit: number = LIST_LIMIT): Promise<MailpitMessageSummary[]> {
    const body = await this.requestJson<MailpitListResponse>(
      'GET',
      `/api/v1/messages?limit=${limit}`,
    );

    return body.messages ?? [];
  }

  /** Full message record, including attachment metadata. */
  async message(id: string): Promise<MailpitMessage> {
    return this.requestJson<MailpitMessage>('GET', `/api/v1/message/${encodeURIComponent(id)}`);
  }

  /** Every currently stored message matching `match`, newest first. */
  async find(match: MailpitMatch, limit: number = LIST_LIMIT): Promise<MailpitMessageSummary[]> {
    const messages = await this.list(limit);

    return messages.filter((message) => matches(message, match));
  }

  /**
   * Polls until a message matching `match` exists, then returns it.
   *
   * @throws when nothing matches within `timeoutMs`.
   */
  async waitForMessage(
    match: MailpitMatch,
    options: number | MailpitWaitOptions = {},
  ): Promise<MailpitMessageSummary> {
    const { timeoutMs, intervalMs } = normalizeWaitOptions(options);
    const deadline = Date.now() + timeoutMs;

    for (;;) {
      const found = await this.find(match);

      if (found.length > 0) {
        return found[0];
      }

      if (Date.now() >= deadline) {
        const seen = await this.list();

        throw new Error(
          `No Mailpit message ${describe(match)} arrived within ${timeoutMs}ms. ${summarize(seen)}`,
        );
      }

      await sleep(Math.min(intervalMs, Math.max(0, deadline - Date.now())));
    }
  }

  /**
   * Waits out a quiet period and asserts that nothing matching `match` arrived.
   *
   * The full window is always elapsed before the assertion passes: the whole
   * point of these specs is the disabled-email direction, and a check that
   * returns early would report a false green for mail that is merely slow.
   *
   * @throws as soon as a matching message shows up.
   */
  async expectNoMessage(
    match: MailpitMatch,
    options: number | MailpitQuietOptions = {},
  ): Promise<void> {
    const { quietMs, intervalMs } = normalizeQuietOptions(options);
    const deadline = Date.now() + quietMs;

    for (;;) {
      const found = await this.find(match);

      if (found.length > 0) {
        throw new Error(
          `Expected no Mailpit message ${describe(match)}, but ${found.length} arrived. ${summarize(found)}`,
        );
      }

      const remaining = deadline - Date.now();

      if (remaining <= 0) {
        return;
      }

      await sleep(Math.min(intervalMs, remaining));
    }
  }

  private async requestJson<T>(method: string, path: string): Promise<T> {
    const url = `${this.baseUrl}${path}`;
    const text = await this.request(method, path);

    if (text.trim() === '') {
      return {} as T;
    }

    try {
      return JSON.parse(text) as T;
    } catch {
      throw new Error(`Mailpit returned non-JSON for ${method} ${url}: ${text.slice(0, 200)}`);
    }
  }

  private async request(method: string, path: string): Promise<string> {
    const url = `${this.baseUrl}${path}`;
    let response: Response;

    try {
      response = await fetch(url, {
        method,
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(DEFAULT_REQUEST_TIMEOUT_MS),
      });
    } catch (error) {
      const reason = error instanceof Error ? error.message : String(error);

      throw new Error(
        `Mailpit request failed: ${method} ${url} (${reason}). ` +
          'Is Mailpit running? Override the endpoint with MAILPIT_URL.',
      );
    }

    if (!response.ok) {
      throw new Error(`Mailpit request failed: ${method} ${url} returned ${response.status}.`);
    }

    return response.text();
  }
}

function matches(message: MailpitMessageSummary, match: MailpitMatch): boolean {
  if (match.to !== undefined && match.to !== '' && !hasRecipient(message, match.to)) {
    return false;
  }

  if (match.subject !== undefined && match.subject !== '') {
    const subject = (message.Subject ?? '').toLowerCase();

    if (!subject.includes(match.subject.toLowerCase())) {
      return false;
    }
  }

  return true;
}

function hasRecipient(message: MailpitMessageSummary, address: string): boolean {
  const wanted = address.trim().toLowerCase();
  const recipients = [...(message.To ?? []), ...(message.Cc ?? []), ...(message.Bcc ?? [])];

  return recipients.some((recipient) => (recipient?.Address ?? '').trim().toLowerCase() === wanted);
}

function describe(match: MailpitMatch): string {
  const parts: string[] = [];

  if (match.to !== undefined && match.to !== '') {
    parts.push(`to "${match.to}"`);
  }

  if (match.subject !== undefined && match.subject !== '') {
    parts.push(`with subject containing "${match.subject}"`);
  }

  return parts.length > 0 ? parts.join(' ') : '(any recipient)';
}

function summarize(messages: MailpitMessageSummary[]): string {
  if (messages.length === 0) {
    return 'Mailbox is empty.';
  }

  const lines = messages
    .slice(0, 10)
    .map((message) => {
      const to = (message.To ?? []).map((recipient) => recipient.Address).join(', ');

      return `  - to [${to}] subject "${message.Subject}"`;
    })
    .join('\n');

  return `Mailbox holds ${messages.length} message(s):\n${lines}`;
}

function normalizeWaitOptions(options: number | MailpitWaitOptions): {
  timeoutMs: number;
  intervalMs: number;
} {
  if (typeof options === 'number') {
    return { timeoutMs: options, intervalMs: DEFAULT_POLL_INTERVAL_MS };
  }

  return {
    timeoutMs: options.timeoutMs ?? DEFAULT_WAIT_TIMEOUT_MS,
    intervalMs: options.intervalMs ?? DEFAULT_POLL_INTERVAL_MS,
  };
}

function normalizeQuietOptions(options: number | MailpitQuietOptions): {
  quietMs: number;
  intervalMs: number;
} {
  if (typeof options === 'number') {
    return { quietMs: options, intervalMs: DEFAULT_POLL_INTERVAL_MS };
  }

  return {
    quietMs: options.quietMs ?? DEFAULT_QUIET_MS,
    intervalMs: options.intervalMs ?? DEFAULT_POLL_INTERVAL_MS,
  };
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => {
    setTimeout(resolve, Math.max(0, ms));
  });
}
