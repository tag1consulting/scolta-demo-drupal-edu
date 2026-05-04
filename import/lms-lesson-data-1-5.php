<?php
/**
 * Lesson data: Modules 1–2 (Lessons 1–6).
 * Adapted from Parts 1–2 of "Building an LLM From Scratch in Rust" by Jeremy Andrews.
 * Blog series: https://www.tag1.com/how-to/part1-tokenization-building-an-llm-from-scratch-in-rust/
 */
function lessons_1_5(): array {
  return [

// ─── Lesson 1 ──────────────────────────────────────────────────────────────
1 => [
  'title'   => 'Why Tokenization Matters',
  'module'  => 1,
  'objectives' => [
    'Explain why LLMs cannot process raw text directly and need tokenization.',
    'Compare character-level, word-level, and subword tokenization strategies.',
    'Describe the role of vocabulary size in model capacity and efficiency.',
  ],
  'content_html' => <<<'HTML'
<h2>Introduction</h2>
<p>When we talk about building a language model "from scratch," the very first challenge is deceptively simple: computers work with numbers, not words. Before a transformer can do anything useful, every piece of text must be converted into a sequence of integers. This conversion process — tokenization — is not just a preprocessing step. It shapes the model's vocabulary, its memory requirements, and ultimately what it can learn.</p>
<p>This lesson covers why tokenization is necessary, what the alternatives look like, and why Byte Pair Encoding (BPE) — the strategy used by GPT-2 and Feste — strikes the right balance for a practical language model.</p>

<h2>What We're Building: Feste</h2>
<p>The Feste project is a GPT-2 style transformer trained entirely on Shakespeare's complete works, implemented from scratch in Rust with no external ML libraries. The name comes from Feste, the witty fool in <em>Twelfth Night</em> — an apt description for a small model that generates plausible-sounding but often absurd Shakespearean text.</p>
<p>The complete source is at <a href="https://github.com/jeremyandrews/feste">github.com/jeremyandrews/feste</a>. Throughout this course we'll trace every component from first principles: tokenizer → tensor operations → model architecture → training → experiments.</p>

<h2>Why We Can't Feed Raw Text to a Neural Network</h2>
<p>Neural networks operate on tensors — multidimensional arrays of floating-point numbers. "The quick brown fox" is not a tensor. To make it one, we need a mapping from text units to integers, and then from integers to embedding vectors.</p>
<p>The question is: <em>what unit of text should we tokenize?</em> The three main options are:</p>
<ul>
  <li><strong>Character-level:</strong> Each character is one token. Vocabulary is tiny (~100 entries for ASCII), but sequences become very long. "Shakespeare" is 11 tokens. The model must learn to combine characters into words at every layer.</li>
  <li><strong>Word-level:</strong> Each word is one token. Vocabulary explodes — English has 170,000+ words, and rare words never seen in training get no representation at all. "uncharacteristically" and "uncharacteristically." are different tokens.</li>
  <li><strong>Subword (BPE, WordPiece, etc.):</strong> Frequently occurring character sequences are merged into single tokens. Common words like "the" and "and" become single tokens; rare words are split into recognizable pieces. "tokenization" might become ["token", "ization"].</li>
</ul>

<h2>Why BPE?</h2>
<p>Byte Pair Encoding was originally developed for data compression. Its core insight: find the most frequently co-occurring pair of symbols in a corpus and merge them into a new symbol. Repeat until you reach your target vocabulary size.</p>
<p>For language models, BPE has three key advantages:</p>
<ol>
  <li><strong>Open vocabulary:</strong> Unknown words at inference time can still be represented as sequences of known subword tokens. A model trained without "CUDA" in its vocabulary can still tokenize it as ["CU", "DA"] or similar.</li>
  <li><strong>Compression efficiency:</strong> Common words become single tokens, so a typical sentence uses far fewer tokens than character-level encoding. This means the model attends over shorter sequences for the same amount of text.</li>
  <li><strong>Morphological awareness:</strong> Related words share token prefixes. "train", "trainer", "training" all start with "train", so the model sees the relationship encoded in the tokenization itself.</li>
</ol>
<p>GPT-2 uses a BPE vocabulary of 50,257 tokens. Feste uses a smaller vocabulary (configurable, typically 8,000–16,000) appropriate for its training corpus.</p>

<h2>Vocabulary Size Trade-offs</h2>
<p>Choosing a vocabulary size involves a genuine trade-off:</p>
<ul>
  <li>A <strong>smaller vocabulary</strong> means more tokens per sentence (longer sequences), cheaper embedding tables, but the model must work harder to understand word structure.</li>
  <li>A <strong>larger vocabulary</strong> means fewer tokens per sentence (shorter sequences, less memory in attention), but the embedding table and output projection matrix become massive.</li>
</ul>
<p>For Feste's Shakespeare corpus (~1MB of text), a vocabulary of 8,000–10,000 tokens provides good coverage without over-fitting the vocabulary to the training domain.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Tokenization bridges raw text and neural network math; without it, LLMs cannot process language.</li>
  <li>Character-level tokenization has tiny vocabulary but very long sequences; word-level has short sequences but a vocabulary explosion and no handling of unknown words.</li>
  <li>BPE (Byte Pair Encoding) finds a pragmatic middle ground: subword units that compress common patterns while remaining extensible to unseen text.</li>
  <li>Vocabulary size is a hyperparameter with real memory and sequence-length trade-offs.</li>
</ul>

<p><em>Adapted from Part 1 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Tokenization converts text to integer sequences that neural networks can process.',
    'BPE merges the most frequent character pairs iteratively until a target vocabulary size is reached.',
    'Vocabulary size trades off sequence length against embedding table size.',
    'BPE gives open-vocabulary coverage: unseen words are split into known subword pieces.',
  ],
  'quiz' => [
    [
      'question' => 'Why is character-level tokenization rarely used for large language models despite its small vocabulary?',
      'answers' => [
        ['text' => 'It produces very long sequences, requiring the model to attend over many more positions to understand word-level meaning.', 'correct' => true, 'explanation' => 'Character-level tokenization turns every character into a token, so a 10-character word requires 10 attention positions. This dramatically increases the sequence length and computational cost of attention.'],
        ['text' => 'The vocabulary is too small to represent punctuation and special characters.', 'correct' => false],
        ['text' => 'Neural networks cannot embed integers smaller than 256.', 'correct' => false],
        ['text' => 'BPE is patented, so character-level tokenization is legally required.', 'correct' => false],
      ],
    ],
    [
      'question' => 'In BPE tokenization, what is merged at each step of the algorithm?',
      'answers' => [
        ['text' => 'The most frequently occurring adjacent pair of symbols in the training corpus.', 'correct' => true, 'explanation' => 'BPE scans the corpus, finds the byte/character pair that appears most often side by side, merges them into a single new symbol, and repeats. This is how common words end up as single tokens while rare words remain split.'],
        ['text' => 'The least frequently occurring pair, to improve coverage of rare words.', 'correct' => false],
        ['text' => 'Random pairs of tokens selected by the tokenizer trainer.', 'correct' => false],
        ['text' => 'Adjacent tokens that share a common prefix character.', 'correct' => false],
      ],
    ],
    [
      'question' => 'How does subword tokenization handle a word that was never seen during tokenizer training?',
      'answers' => [
        ['text' => 'It splits the word into known subword pieces from the vocabulary.', 'correct' => true, 'explanation' => 'BPE starts from individual characters/bytes, which are always in the vocabulary. An unseen word is split into the longest matching subword tokens available. This gives "open vocabulary" coverage.'],
        ['text' => 'It maps the word to a special <UNK> token.', 'correct' => false],
        ['text' => 'It skips the word entirely and continues with the next word.', 'correct' => false],
        ['text' => 'It expands the vocabulary at inference time by adding the new word.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Increasing vocabulary size from 8,000 to 50,000 tokens primarily affects which two components of the model?',
      'answers' => [
        ['text' => 'The embedding table and the output projection (lm_head) matrix, both of which scale linearly with vocabulary size.', 'correct' => true, 'explanation' => 'The embedding table is vocab_size × embed_dim, and the output projection is embed_dim × vocab_size. Doubling the vocabulary roughly doubles the parameters in these two matrices.'],
        ['text' => 'The attention heads and the feedforward hidden layer size.', 'correct' => false],
        ['text' => 'The number of transformer layers and the layer normalization parameters.', 'correct' => false],
        ['text' => 'The learning rate and the weight decay hyperparameters.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 2 ──────────────────────────────────────────────────────────────
2 => [
  'title'   => 'The BPE Algorithm',
  'module'  => 1,
  'objectives' => [
    'Trace the BPE merge process step-by-step on a small example.',
    'Explain how compression ratio relates to vocabulary size.',
    'Understand why merge order matters and is saved as part of the tokenizer.',
  ],
  'content_html' => <<<'HTML'
<h2>The Algorithm in Detail</h2>
<p>BPE is a bottom-up algorithm. It starts with the smallest possible units — individual bytes — and greedily merges the most common adjacent pairs until the vocabulary reaches a target size.</p>
<p>Here's a minimal example. Suppose our entire corpus is:</p>
<pre><code>low lower newest widest
</code></pre>
<p>We first split into characters and count word frequencies:</p>
<pre><code>l o w         → 1
l o w e r     → 1
n e w e s t   → 1
w i d e s t   → 1
</code></pre>
<p>Step 1: Find the most frequent pair. "e s" appears twice (in "newest" and "widest"). Merge → "es".</p>
<pre><code>l o w         → 1
l o w e r     → 1
n e w es t    → 1
w i d es t    → 1
</code></pre>
<p>Step 2: "es t" is now the most frequent pair (appears twice). Merge → "est".</p>
<pre><code>l o w         → 1
l o w e r     → 1
n e w est     → 1
w i d est     → 1
</code></pre>
<p>This continues until the vocabulary reaches the desired size. The <em>merge table</em> — the ordered list of pairs that were merged — is saved alongside the vocabulary. At inference time, the tokenizer applies these merges in the same order.</p>

<h2>Compression Ratio</h2>
<p>Compression ratio measures how many raw bytes become how many tokens. A ratio of 4.0 means every 4 bytes in the input becomes 1 token on average. GPT-4's tokenizer achieves around 4.0 on English text. Feste's smaller vocabulary achieves 3.0–3.5.</p>
<p>A higher compression ratio means:</p>
<ul>
  <li>Shorter sequences → less memory in the attention mechanism (attention scales O(n²) with sequence length)</li>
  <li>Larger individual tokens → each token carries more semantic content on average</li>
</ul>
<p>But there's a ceiling: at some vocabulary size, adding more merges yields diminishing compression returns while the embedding table keeps growing.</p>

<h2>Implementation Details in Rust</h2>
<p>Feste's tokenizer is implemented in <code>src/tokenizer/</code>. The core training loop looks like:</p>
<pre><code class="language-rust">// Count all adjacent pairs in the current token sequences
let mut pair_counts: HashMap&lt;(u32, u32), u32&gt; = HashMap::new();
for sequence in &amp;token_sequences {
    for window in sequence.windows(2) {
        *pair_counts.entry((window[0], window[1])).or_insert(0) += 1;
    }
}

// Find the most frequent pair
let best_pair = pair_counts
    .iter()
    .max_by_key(|(_, count)| *count)
    .map(|(pair, _)| *pair)
    .unwrap();

// Merge all occurrences of best_pair into a new token
let new_token_id = vocab.len() as u32;
vocab.insert(new_token_id, merge_bytes(best_pair, &amp;vocab));
merges.push((best_pair.0, best_pair.1, new_token_id));
apply_merge(&amp;mut token_sequences, best_pair, new_token_id);
</code></pre>
<p>The key data structures are:</p>
<ul>
  <li><code>vocab: HashMap&lt;u32, Vec&lt;u8&gt;&gt;</code> — maps token ID to the bytes it represents</li>
  <li><code>merges: Vec&lt;(u32, u32, u32)&gt;</code> — the ordered merge table: (left_id, right_id, new_id)</li>
</ul>
<p>The merge table is what gets saved to disk and loaded at inference time. The vocabulary can be reconstructed from it.</p>

<h2>Special Tokens</h2>
<p>BPE tokenizers typically include a small set of special tokens beyond the merge-derived vocabulary:</p>
<ul>
  <li><code>&lt;|endoftext|&gt;</code> — marks the boundary between training documents</li>
  <li><code>&lt;|pad|&gt;</code> — used to pad sequences in a batch to equal length</li>
</ul>
<p>These are assigned IDs (usually at the end of the vocabulary, e.g., 8000 and 8001) and handled separately from the BPE merge process.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>BPE starts from individual bytes and greedily merges the most frequent adjacent pair, repeatedly.</li>
  <li>The merge table is an ordered list — the order matters because later merges build on earlier ones.</li>
  <li>Compression ratio (bytes per token) measures tokenization efficiency; higher is generally better up to a point.</li>
  <li>Special tokens like <code>&lt;|endoftext|&gt;</code> are added outside the BPE process and have fixed IDs.</li>
</ul>
<p><em>Adapted from Part 1 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'BPE starts from individual bytes and greedily merges the most frequent adjacent pair.',
    'The merge table is saved in order; inference replays merges in the same sequence.',
    'Compression ratio measures how many input bytes become one token on average.',
    'Special tokens are added outside the BPE process with reserved IDs.',
  ],
  'quiz' => [
    [
      'question' => 'In BPE training, why must the merge table be stored in its original order?',
      'answers' => [
        ['text' => 'Later merges depend on earlier ones — a merged token can itself become part of a subsequent merge.', 'correct' => true, 'explanation' => 'For example, "e" and "s" might be merged to "es" first, then "es" and "t" are merged to "est". If you applied the second merge without the first, "est" wouldn\'t exist yet.'],
        ['text' => 'The order determines the token IDs, which must be sequential to avoid index errors.', 'correct' => false],
        ['text' => 'Tokenizer decoders require alphabetical ordering of the merge table.', 'correct' => false],
        ['text' => 'The GPU cannot process an unordered hash map during inference.', 'correct' => false],
      ],
    ],
    [
      'question' => 'A tokenizer achieves a compression ratio of 4.0. What does this mean?',
      'answers' => [
        ['text' => 'On average, 4 bytes of input text are represented as 1 token.', 'correct' => true, 'explanation' => 'Compression ratio = bytes / tokens. Ratio 4.0 means the tokenizer condenses 4 raw bytes into a single token on average, reducing sequence length by 4×.'],
        ['text' => 'The vocabulary is exactly 4 times larger than the training corpus in bytes.', 'correct' => false],
        ['text' => 'The model runs 4× faster than a character-level model.', 'correct' => false],
        ['text' => 'Four merge operations are needed per word on average.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the role of the <code>&lt;|endoftext|&gt;</code> special token?',
      'answers' => [
        ['text' => 'It marks the boundary between separate training documents so the model does not learn to attend across document boundaries.', 'correct' => true, 'explanation' => 'During training, documents are concatenated into long sequences. The endoftext token tells the model "what came before this boundary is from a different document." This prevents spurious cross-document dependencies.'],
        ['text' => 'It signals the tokenizer to stop applying BPE merges.', 'correct' => false],
        ['text' => 'It is inserted between every sentence to improve grammatical understanding.', 'correct' => false],
        ['text' => 'It pads short sequences to the maximum context length.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 3 ──────────────────────────────────────────────────────────────
3 => [
  'title'   => 'Building the Tokenizer in Rust',
  'module'  => 1,
  'objectives' => [
    'Explain how Feste\'s tokenizer is trained on a text corpus.',
    'Describe the encode and decode functions and their data flow.',
    'Evaluate tokenizer output on sample Shakespeare text.',
  ],
  'content_html' => <<<'HTML'
<h2>Training the Tokenizer</h2>
<p>Before training the language model itself, we need to train the tokenizer on the target corpus. For Feste this is Shakespeare's complete works — approximately 1MB of text available as a single file.</p>
<p>The training process:</p>
<ol>
  <li>Read the raw UTF-8 corpus into memory.</li>
  <li>Initialize the vocabulary with all 256 possible byte values (IDs 0–255).</li>
  <li>Repeatedly find the most frequent adjacent pair and merge it, up to <code>vocab_size - 256</code> times.</li>
  <li>Save the merge table and vocabulary to disk.</li>
</ol>
<p>For a vocabulary of 10,000 tokens, we perform 9,744 merges (10,000 − 256 base byte tokens). On a 1MB corpus in Rust, this completes in seconds.</p>

<h2>The Encode Function</h2>
<p>Given a string to encode:</p>
<ol>
  <li>Convert to UTF-8 bytes.</li>
  <li>Initialize each byte as its own token (ID = byte value).</li>
  <li>Iterate through the merge table in order. For each merge rule <code>(left, right) → new</code>, scan the token sequence and replace all occurrences of the pair <code>(left, right)</code> with <code>new</code>.</li>
  <li>Return the resulting token ID sequence.</li>
</ol>
<pre><code class="language-rust">pub fn encode(&amp;self, text: &amp;str) -&gt; Vec&lt;u32&gt; {
    // Start: one token per byte
    let mut tokens: Vec&lt;u32&gt; = text.as_bytes()
        .iter()
        .map(|&amp;b| b as u32)
        .collect();

    // Apply merges in training order
    for &amp;(left, right, merged) in &amp;self.merges {
        let mut i = 0;
        let mut output = Vec::with_capacity(tokens.len());
        while i &lt; tokens.len() {
            if i + 1 &lt; tokens.len() &amp;&amp; tokens[i] == left &amp;&amp; tokens[i + 1] == right {
                output.push(merged);
                i += 2;
            } else {
                output.push(tokens[i]);
                i += 1;
            }
        }
        tokens = output;
    }
    tokens
}
</code></pre>

<h2>The Decode Function</h2>
<p>Decoding reverses the process: given a sequence of token IDs, look up each ID in the vocabulary to get its byte sequence, concatenate, and interpret as UTF-8:</p>
<pre><code class="language-rust">pub fn decode(&amp;self, tokens: &amp;[u32]) -&gt; String {
    let bytes: Vec&lt;u8&gt; = tokens
        .iter()
        .flat_map(|&amp;id| self.vocab[&amp;id].iter().copied())
        .collect();
    String::from_utf8_lossy(&amp;bytes).into_owned()
}
</code></pre>

<h2>Training Results</h2>
<p>After training on the Shakespeare corpus with vocabulary size 10,000:</p>
<ul>
  <li>Compression ratio: ~3.4 (3.4 characters per token on average)</li>
  <li>Common Shakespeare words as single tokens: "thee", "thou", "thy", "hath", "doth", "wherefore"</li>
  <li>Common English words as single tokens: "the", "and", "that", "with", "have", "this"</li>
  <li>Rare or compound words split into 2–3 tokens</li>
</ul>
<p>The tokenizer has learned Shakespeare's vocabulary organically: words that appear frequently in the plays become their own tokens.</p>

<h2>Running the Examples</h2>
<p>From the Feste repository:</p>
<pre><code class="language-bash">cargo run --example tokenize -- --text "To be or not to be, that is the question"
# Output: [1204, 307, 389, 407, 281, 307, 11, 356, 318, 262, 1808]
# Tokens: ["To", " be", " or", " not", " to", " be", ",", " that", " is", " the", " question"]
</code></pre>
<p>Notice that the space before each word is included in the token. This is standard practice — it ensures "be" at the start of a sentence tokenizes the same way as " be" in the middle.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Tokenizer training requires only a text corpus and a target vocabulary size; no GPU needed.</li>
  <li>Encoding applies all merge rules in training order; decoding concatenates byte sequences from the vocabulary.</li>
  <li>The tokenizer learns domain vocabulary: Shakespeare-specific words like "hath" and "thou" become single tokens.</li>
  <li>Leading spaces are included in tokens so position within a sentence doesn't affect tokenization.</li>
</ul>
<p><em>Adapted from Part 1 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Tokenizer training reads a corpus, initializes byte vocabulary, and performs greedy pair merges.',
    'Encoding applies merge rules in order; decoding looks up byte sequences and concatenates.',
    'Domain-specific training learns corpus vocabulary: Shakespeare words become single tokens.',
    'Leading spaces are baked into tokens to make position-invariant tokenization.',
  ],
  'quiz' => [
    [
      'question' => 'Why does Feste\'s tokenizer initialize the vocabulary with all 256 byte values?',
      'answers' => [
        ['text' => 'To guarantee that any UTF-8 input can be tokenized — every byte has a valid token ID before any merges are applied.', 'correct' => true, 'explanation' => 'Starting with 256 byte tokens ensures the tokenizer is truly open-vocabulary. Even a byte sequence never seen in training can be represented as individual byte tokens (IDs 0–255).'],
        ['text' => 'Because the transformer embedding table requires exactly 256 base tokens to initialize weights.', 'correct' => false],
        ['text' => 'Because 256 is the maximum vocabulary size allowed by the BPE algorithm.', 'correct' => false],
        ['text' => 'To pre-populate the merge table with all possible single-byte merges.', 'correct' => false],
      ],
    ],
    [
      'question' => 'In the encode function, why are merge rules applied in their original training order rather than by frequency?',
      'answers' => [
        ['text' => 'Later merges depend on earlier ones: a pair can only be merged if both tokens already exist in the current sequence.', 'correct' => true, 'explanation' => 'For example, "es" must be merged before "est" can be merged, because "est" is composed of "es" (itself a merge result) and "t". Applying them out of order would fail to find the "est" pair.'],
        ['text' => 'Training order gives the highest compression ratio; frequency order would produce a suboptimal tokenization.', 'correct' => false],
        ['text' => 'The Rust HashMap requires keys to be iterated in insertion order for performance.', 'correct' => false],
        ['text' => 'More frequent merges contain more semantic information and should be applied last.', 'correct' => false],
      ],
    ],
    [
      'question' => 'After training on Shakespeare, the word "wherefore" becomes a single token, but "consequently" is split. What does this tell you?',
      'answers' => [
        ['text' => '"Wherefore" appears frequently in Shakespeare; "consequently" is rare in the corpus, so its character pairs were never merged all the way to a single token.', 'correct' => true, 'explanation' => 'BPE merges high-frequency pairs first. A word that appears hundreds of times will eventually merge into a single token; a rare word won\'t accumulate enough pair frequency to merge fully.'],
        ['text' => '"Wherefore" is shorter, and BPE always produces single tokens for words under 10 characters.', 'correct' => false],
        ['text' => 'The tokenizer was configured to keep all archaic English words as single tokens.', 'correct' => false],
        ['text' => '"Consequently" contains a silent letter, which BPE cannot handle.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 4 ──────────────────────────────────────────────────────────────
4 => [
  'title'   => 'Tensors and Core Operations',
  'module'  => 2,
  'objectives' => [
    'Define tensors and explain their role as the fundamental data structure in ML.',
    'Describe how tensors are laid out in memory and why layout affects performance.',
    'Implement element-wise and reduction operations on tensors.',
  ],
  'content_html' => <<<'HTML'
<h2>What Is a Tensor?</h2>
<p>A tensor is an n-dimensional array of numbers. In Rust (and in Feste), a tensor is defined by three things:</p>
<ul>
  <li><strong>Data:</strong> a flat <code>Vec&lt;f32&gt;</code> holding all values in memory.</li>
  <li><strong>Shape:</strong> a <code>Vec&lt;usize&gt;</code> describing the size of each dimension. A shape of <code>[32, 512]</code> means 32 rows, 512 columns — a matrix.</li>
  <li><strong>Strides:</strong> a <code>Vec&lt;usize&gt;</code> describing how many elements to skip in the flat data to advance one step along each dimension.</li>
</ul>
<p>Common tensor ranks in a transformer:</p>
<table>
<thead><tr><th>Rank</th><th>Shape example</th><th>What it represents</th></tr></thead>
<tbody>
<tr><td>1D (vector)</td><td>[512]</td><td>A single embedding vector</td></tr>
<tr><td>2D (matrix)</td><td>[128, 512]</td><td>A batch of 128 token embeddings</td></tr>
<tr><td>3D</td><td>[12, 128, 64]</td><td>12 attention heads × 128 sequence positions × 64-dim keys</td></tr>
<tr><td>4D</td><td>[8, 12, 128, 128]</td><td>Batch × heads × seq × seq attention scores</td></tr>
</tbody>
</table>

<h2>Memory Layout and Strides</h2>
<p>The flat data buffer stores elements in row-major order (also called C order): the last index varies fastest. For a matrix of shape <code>[3, 4]</code>:</p>
<pre><code>Index [0,0] [0,1] [0,2] [0,3] [1,0] [1,1] ... [2,3]
Flat:    0     1     2     3     4     5  ...   11
</code></pre>
<p>The strides for this matrix are <code>[4, 1]</code>: to move one row, skip 4 elements; to move one column, skip 1 element.</p>
<p>Strides enable zero-copy operations like transpose. Instead of copying data, transposing swaps strides: <code>[4, 1]</code> becomes <code>[1, 4]</code>. The same flat data is now interpreted as a <code>[4, 3]</code> matrix.</p>

<h2>Creating Tensors</h2>
<p>Feste provides several constructors:</p>
<pre><code class="language-rust">// Zeros tensor: shape [2, 3]
let t = Tensor::zeros(&amp;[2, 3]);

// Random normal distribution (for weight initialization)
let t = Tensor::randn(&amp;[512, 512]);

// From existing data
let t = Tensor::from_vec(vec![1.0, 2.0, 3.0, 4.0], &amp;[2, 2]);
</code></pre>

<h2>Core Operations</h2>
<h3>Element-wise Operations</h3>
<p>These apply a function to every pair of corresponding elements:</p>
<pre><code class="language-rust">let c = a.add(&amp;b);   // c[i,j] = a[i,j] + b[i,j]
let c = a.mul(&amp;b);   // element-wise multiply (Hadamard product)
let c = a.sub(&amp;b);
</code></pre>
<p>Element-wise operations require identical shapes (or compatible shapes for broadcasting — covered in Module 2, Lesson 6).</p>

<h3>Reduction Operations</h3>
<p>Reduce along one or more dimensions:</p>
<pre><code class="language-rust">let s = t.sum();          // scalar: sum of all elements
let row_sums = t.sum_dim(1);  // sum along dimension 1 → shape [rows]
let mean = t.mean();
let max = t.max_dim(0);   // max along dimension 0
</code></pre>

<h3>Scalar Operations</h3>
<pre><code class="language-rust">let scaled = t.mul_scalar(0.5);   // multiply every element by 0.5
let shifted = t.add_scalar(-1.0); // subtract 1.0 from every element
</code></pre>

<h2>Performance Considerations</h2>
<p>The biggest performance issue in tensor operations is cache behavior. Modern CPUs fetch data in cache lines (~64 bytes). Iterating through a matrix row-by-row accesses contiguous memory → fast. Iterating column-by-column jumps by stride → causes cache misses → slow.</p>
<p>This is why the choice between row-major and column-major storage matters so much for matrix multiplication, which accesses both rows and columns. Feste uses row-major throughout and transposes matrices to optimize access patterns before multiplication.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>A tensor is a flat data buffer plus shape and strides metadata — the mathematical object is multidimensional but the storage is always 1D.</li>
  <li>Row-major (C order) storage means the last index varies fastest in memory.</li>
  <li>Strides enable zero-copy transpose: swap the stride values, same data.</li>
  <li>Cache efficiency drives the performance of element-wise and reduction operations.</li>
</ul>
<p><em>Adapted from Part 2 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Tensors are flat memory buffers with shape and stride metadata; rank describes how many dimensions.',
    'Row-major storage: last index is contiguous in memory; strides encode how to navigate dimensions.',
    'Transpose is zero-copy: swap strides, same data buffer.',
    'Cache locality is the primary performance concern in element-wise and reduction operations.',
  ],
  'quiz' => [
    [
      'question' => 'A tensor has shape [4, 6] and row-major (C-order) strides. What are the strides?',
      'answers' => [
        ['text' => '[6, 1] — moving one row requires skipping 6 elements; moving one column skips 1.', 'correct' => true, 'explanation' => 'In row-major order, the stride for the last dimension is always 1 (elements are contiguous). The stride for the first dimension equals the size of all subsequent dimensions: 6.'],
        ['text' => '[1, 4] — moving one column skips 4 elements (the number of rows).', 'correct' => false],
        ['text' => '[4, 6] — strides always equal the shape.', 'correct' => false],
        ['text' => '[24, 1] — the total number of elements divided by the last dimension.', 'correct' => false],
      ],
    ],
    [
      'question' => 'How does transposing a tensor work in Feste without copying data?',
      'answers' => [
        ['text' => 'By swapping the shape dimensions and their corresponding strides; the flat data buffer is unchanged.', 'correct' => true, 'explanation' => 'Transposing [4, 6] with strides [6, 1] gives shape [6, 4] with strides [1, 6]. The indexing formula row*stride[0] + col*stride[1] now accesses the data column-first instead of row-first.'],
        ['text' => 'By reversing the order of elements in the flat data buffer.', 'correct' => false],
        ['text' => 'By creating a new tensor and copying each element to its transposed position.', 'correct' => false],
        ['text' => 'By applying a special SIMD instruction that swaps rows and columns in-place.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Why does iterating through a matrix column-by-column cause poor cache performance?',
      'answers' => [
        ['text' => 'In row-major storage, adjacent elements in a column are separated by (row_size) positions in memory, so each access loads a new cache line.', 'correct' => true, 'explanation' => 'A CPU cache line holds ~64 bytes = ~16 floats. Row-major stores row elements contiguously. Accessing column elements skips entire rows, so almost every access is a cache miss.'],
        ['text' => 'The CPU can only prefetch data in ascending memory order, and columns are stored in descending order.', 'correct' => false],
        ['text' => 'Column-major operations require more floating-point additions than row-major.', 'correct' => false],
        ['text' => 'SIMD vectorization is disabled automatically when accessing non-contiguous memory.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 5 ──────────────────────────────────────────────────────────────
5 => [
  'title'   => 'Matrix Multiplication and Performance',
  'module'  => 2,
  'objectives' => [
    'Implement a correct naive matrix multiplication and analyze its complexity.',
    'Explain cache blocking and why it dramatically improves matmul performance.',
    'Describe how Rayon parallelizes matmul across CPU cores.',
  ],
  'content_html' => <<<'HTML'
<h2>Why Matrix Multiplication Matters</h2>
<p>Matrix multiplication is the single most compute-intensive operation in a transformer. Every linear layer, every attention score computation, and every output projection is a matrix multiply. Getting matmul fast is not optional — it determines whether training is measured in hours or months.</p>

<h2>The Naive Implementation</h2>
<p>For matrices A (m×k) and B (k×n), the output C (m×n) is:</p>
<pre><code>C[i, j] = Σ A[i, k] * B[k, j]   for k in 0..K
</code></pre>
<p>In Rust:</p>
<pre><code class="language-rust">pub fn matmul_naive(a: &amp;Tensor, b: &amp;Tensor) -&gt; Tensor {
    let (m, k) = (a.shape[0], a.shape[1]);
    let n = b.shape[1];
    let mut c = Tensor::zeros(&amp;[m, n]);
    for i in 0..m {
        for j in 0..n {
            let mut sum = 0.0_f32;
            for kk in 0..k {
                sum += a[[i, kk]] * b[[kk, j]];
            }
            c[[i, j]] = sum;
        }
    }
    c
}
</code></pre>
<p>Complexity: O(m × n × k). For two 512×512 matrices, that's 134 million multiply-accumulate operations. The naive implementation does this with terrible cache behavior: accessing B column-by-column causes continuous cache misses.</p>

<h2>Cache Blocking (Tiled Matmul)</h2>
<p>The key insight: if we can fit a block of A and a block of B in the L1 cache simultaneously, we can reuse those values many times before evicting them.</p>
<pre><code class="language-rust">const BLOCK: usize = 64;  // tune for L1 cache size

pub fn matmul_blocked(a: &amp;Tensor, b: &amp;Tensor) -&gt; Tensor {
    let (m, k, n) = (a.shape[0], a.shape[1], b.shape[1]);
    let mut c = Tensor::zeros(&amp;[m, n]);

    for i in (0..m).step_by(BLOCK) {
        for kk in (0..k).step_by(BLOCK) {
            for j in (0..n).step_by(BLOCK) {
                // Process one BLOCK×BLOCK tile
                let i_end = (i + BLOCK).min(m);
                let k_end = (kk + BLOCK).min(k);
                let j_end = (j + BLOCK).min(n);
                for ii in i..i_end {
                    for kkk in kk..k_end {
                        let a_val = a[[ii, kkk]];
                        for jj in j..j_end {
                            c[[ii, jj]] += a_val * b[[kkk, jj]];
                        }
                    }
                }
            }
        }
    }
    c
}
</code></pre>
<p>Why this works: for each tile, we load a 64×64 block of A (~16KB) and B (~16KB) into cache. Then we compute all 64×64×64 = 262,144 operations against those cached values. The data stays in cache across the inner loop, eliminating cache misses for the inner computation.</p>

<h2>SIMD Vectorization</h2>
<p>Modern CPUs have SIMD (Single Instruction, Multiple Data) units that process 4–16 floats in a single instruction. Rust's compiler auto-vectorizes tight inner loops when the access pattern is contiguous and the loop has no data dependencies.</p>
<p>The innermost loop of the blocked matmul — iterating <code>jj</code> over contiguous elements of C and B — is auto-vectorized by the Rust compiler with <code>-C opt-level=3</code>. Enabling AVX2 (via <code>RUSTFLAGS="-C target-cpu=native"</code>) allows processing 8 floats per instruction, effectively giving 8× throughput on that inner loop.</p>

<h2>Parallelization with Rayon</h2>
<p>The outermost loop over row blocks of A is embarrassingly parallel — each row block of C can be computed independently. Rayon's work-stealing thread pool makes this a one-line change:</p>
<pre><code class="language-rust">use rayon::prelude::*;

(0..m).into_par_iter().step_by(BLOCK).for_each(|i| {
    // compute rows i..min(i+BLOCK, m) of C
});
</code></pre>
<p>On an 8-core machine, this delivers roughly 6–7× speedup over single-threaded (accounting for thread overhead and memory bandwidth limits).</p>

<h2>Benchmarks</h2>
<p>For 512×512 × 512×512 matrix multiplication on a modern laptop CPU:</p>
<table>
<thead><tr><th>Implementation</th><th>Time</th><th>Speedup</th></tr></thead>
<tbody>
<tr><td>Naive</td><td>~1,200 ms</td><td>1×</td></tr>
<tr><td>Cache-blocked</td><td>~180 ms</td><td>6.7×</td></tr>
<tr><td>Blocked + Rayon (8 cores)</td><td>~28 ms</td><td>43×</td></tr>
</tbody>
</table>
<p>The lesson: algorithmic improvements (cache blocking) often outpace raw parallelism. A 6.7× speedup from better memory access patterns alone, before adding a single extra core.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Naive matmul has O(n³) operations and terrible cache behavior for the inner column access.</li>
  <li>Cache blocking (tiling) reuses loaded data across many operations before eviction, dramatically improving cache hit rates.</li>
  <li>SIMD auto-vectorization multiplies throughput on the innermost loop when memory is contiguous.</li>
  <li>Rayon parallelizes the outer tile loop across CPU cores with minimal code change.</li>
</ul>
<p><em>Adapted from Part 2 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Matmul is O(m×n×k); naive implementation suffers from cache misses on B column access.',
    'Cache blocking fits tiles into L1 cache, reusing loaded data and slashing cache misses.',
    'SIMD auto-vectorization gives 4–8× inner-loop throughput on contiguous memory.',
    'Rayon parallelizes outer tile loops; algorithmic wins from blocking often exceed parallelism gains.',
  ],
  'quiz' => [
    [
      'question' => 'Why does the naive matrix multiplication access matrix B with poor cache performance?',
      'answers' => [
        ['text' => 'The innermost loop iterates over column j of B, but B is stored in row-major order, so each B[k, j] access skips n elements in memory.', 'correct' => true, 'explanation' => 'For B stored in row-major order with shape [k, n], element B[k, j] is at offset k*n + j. As k increments in the inner loop, we jump n floats ahead — each step likely causing a cache miss.'],
        ['text' => 'B is stored in column-major order in Feste, so row access is non-contiguous.', 'correct' => false],
        ['text' => 'Multiplying two matrices requires reading B twice, exceeding the L1 cache size.', 'correct' => false],
        ['text' => 'SIMD vectorization is disabled for matrix B but not matrix A.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the main benefit of cache blocking (tiling) in matrix multiplication?',
      'answers' => [
        ['text' => 'A block of A and B fits in L1 cache, allowing many multiplications to reuse cached data before eviction.', 'correct' => true, 'explanation' => 'With a 64×64 block size, ~16KB of A and ~16KB of B are loaded into the ~32KB L1 cache. The 64×64×64 = 262K operations in the inner tile all hit cache, dramatically reducing memory traffic.'],
        ['text' => 'Blocking reduces the total number of multiply-accumulate operations needed.', 'correct' => false],
        ['text' => 'Blocking enables the compiler to auto-vectorize loops it previously could not.', 'correct' => false],
        ['text' => 'Blocking distributes work evenly across CPU cores for better parallelism.', 'correct' => false],
      ],
    ],
    [
      'question' => 'The benchmark shows cache-blocked matmul is 6.7× faster than naive, while adding 8 cores gives a further 6.4× on top. What does this comparison suggest?',
      'answers' => [
        ['text' => 'Algorithmic/memory improvements can equal or exceed the benefit of hardware parallelism; both are necessary for best performance.', 'correct' => true, 'explanation' => 'A single-core blocked implementation is 6.7× faster than naive. Eight cores give another 6.4×. Both improvements are multiplicative and roughly equal in magnitude — optimizing the algorithm before adding hardware is often the higher-leverage step.'],
        ['text' => 'CPU cores are the bottleneck; algorithm choice matters less than hardware count.', 'correct' => false],
        ['text' => 'Cache blocking only helps on single-core machines and is redundant with parallelism.', 'correct' => false],
        ['text' => 'The speedups suggest the workload is memory-bound and will not benefit further from SIMD.', 'correct' => false],
      ],
    ],
  ],
],

  ]; // end return
} // end function
