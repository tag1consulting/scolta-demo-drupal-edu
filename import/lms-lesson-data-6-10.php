<?php
/**
 * Lesson data: Modules 2–4 (Lessons 6–10).
 * Adapted from Parts 2–4 of "Building an LLM From Scratch in Rust" by Jeremy Andrews.
 */
function lessons_6_10(): array {
  return [

// ─── Lesson 6 ──────────────────────────────────────────────────────────────
6 => [
  'title'   => 'Softmax, Broadcasting, and Advanced Operations',
  'module'  => 2,
  'objectives' => [
    'Implement numerically stable softmax and explain why naïve softmax overflows.',
    'Apply broadcasting rules to add or multiply tensors of different shapes.',
    'Use masked fill to implement causal masking in attention score tensors.',
  ],
  'content_html' => <<<'HTML'
<h2>Softmax and Numerical Stability</h2>
<p>Softmax converts a vector of arbitrary real numbers into a probability distribution. For a vector x:</p>
<pre><code>softmax(x)[i] = exp(x[i]) / Σ exp(x[j])
</code></pre>
<p>The problem: <code>exp(x[i])</code> overflows to infinity when x[i] is large (e.g., exp(1000) = ∞ in float32). The fix is the "stable softmax" identity:</p>
<pre><code>softmax(x) = softmax(x − max(x))
</code></pre>
<p>Subtracting the maximum shifts the exponent range to (−∞, 0], making the largest exponent exp(0)=1 and all others smaller. This eliminates overflow while producing identical probabilities.</p>
<pre><code class="language-rust">pub fn softmax(&amp;self, dim: usize) -&gt; Tensor {
    let max_vals = self.max_dim(dim, true);
    let shifted = self.sub(&amp;max_vals);
    let exp_vals = shifted.exp();
    let sum_exp = exp_vals.sum_dim(dim, true);
    exp_vals.div(&amp;sum_exp)
}
</code></pre>
<p>In the transformer, softmax is applied to attention scores. Large scores (before softmax) indicate high affinity between a query and a key position.</p>

<h2>Broadcasting</h2>
<p>Broadcasting allows operations between tensors of different shapes, following NumPy-style rules:</p>
<ol>
  <li>Align shapes from the right (trailing dimensions).</li>
  <li>Dimensions must be equal, or one of them must be 1 (which gets expanded to match).</li>
</ol>
<p>Examples:</p>
<pre><code>Shape [4, 512] + Shape [512]   → [512] broadcasts to [4, 512] ✓
Shape [8, 12, 128, 128] + Shape [1, 12, 1, 128] → broadcasts ✓
Shape [3, 4] + Shape [2, 4]   → ERROR: 3 ≠ 2 and neither is 1
</code></pre>
<p>In attention, we add a bias of shape <code>[1, 1, 1, seq_len]</code> to scores of shape <code>[batch, heads, seq_len, seq_len]</code>. Broadcasting expands the bias to match without allocating memory for the full tensor.</p>
<pre><code class="language-rust">pub fn broadcast_add(&amp;self, other: &amp;Tensor) -&gt; Tensor {
    let out_shape = self.broadcast_shape(other);
    // Compute output using stride-based index mapping
    let mut result = Tensor::zeros(&amp;out_shape);
    for idx in result.indices() {
        result[&amp;idx] = self.get_broadcast(&amp;idx) + other.get_broadcast(&amp;idx);
    }
    result
}
</code></pre>

<h2>Masked Fill</h2>
<p>Causal language models must not allow position <em>i</em> to attend to positions <em>j &gt; i</em> (future tokens). We enforce this with a causal mask: a lower-triangular boolean matrix where <code>mask[i,j] = true</code> if j ≤ i.</p>
<p>The masked fill operation sets all positions where the mask is false to negative infinity, so they become zero after softmax:</p>
<pre><code class="language-rust">pub fn masked_fill(&amp;self, mask: &amp;BoolTensor, fill_value: f32) -&gt; Tensor {
    let mut result = self.clone();
    for (i, &amp;m) in mask.data.iter().enumerate() {
        if !m { result.data[i] = fill_value; }
    }
    result
}

// Usage in attention:
let mask = causal_mask(seq_len);              // lower triangular
let scores = scores.masked_fill(&amp;mask, f32::NEG_INFINITY);
let weights = scores.softmax(dim: -1);        // -inf → 0.0 after softmax
</code></pre>

<h2>Reshaping and Transposing</h2>
<p>Two operations that are central to the attention mechanism:</p>
<ul>
  <li><strong>reshape(new_shape):</strong> Changes the shape without moving data, as long as the total element count is unchanged. <code>[batch, seq, embed_dim]</code> → <code>[batch, seq, n_heads, head_dim]</code>.</li>
  <li><strong>transpose(dim0, dim1):</strong> Swaps two dimensions by swapping their strides. <code>[batch, seq, n_heads, head_dim]</code>.transpose(1,2) → <code>[batch, n_heads, seq, head_dim]</code>.</li>
</ul>

<h2>Statistical Operations</h2>
<p>Used in layer normalization:</p>
<pre><code class="language-rust">let mean = x.mean_dim(-1, true);     // mean over last dimension, keep dims
let variance = x.var_dim(-1, true);  // variance over last dimension
let normalized = (x - mean) / (variance + 1e-5).sqrt();
</code></pre>

<h2>Key Takeaways</h2>
<ul>
  <li>Stable softmax subtracts the max before exponentiation, preventing float32 overflow.</li>
  <li>Broadcasting expands size-1 dimensions to match a larger tensor without data copies.</li>
  <li>Masked fill sets future positions to −∞ before softmax, implementing causal attention.</li>
  <li>Reshape and transpose are zero-copy when the data is contiguous; they only modify shape/stride metadata.</li>
</ul>
<p><em>Adapted from Part 2 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Stable softmax = subtract max before exp(); same probabilities, no overflow.',
    'Broadcasting expands size-1 dimensions implicitly; shapes must be compatible from the right.',
    'Masked fill + −∞ → 0 after softmax implements causal masking with no extra memory.',
    'Reshape/transpose manipulate strides, not data — zero-copy when tensor is contiguous.',
  ],
  'quiz' => [
    [
      'question' => 'Why does naïve softmax overflow for large input values, and how does the stable version fix this?',
      'answers' => [
        ['text' => 'exp(x) overflows f32 for x > ~88. Subtracting max(x) shifts all values to ≤0, keeping exp in range while leaving softmax values unchanged (mathematically identical).', 'correct' => true, 'explanation' => 'exp(88) ≈ 1.65×10³⁸ (near f32 max). exp(89) = inf. Subtracting max means the largest exp is exp(0)=1, all others are smaller. The softmax ratio is identical because max(x) cancels in numerator and denominator.'],
        ['text' => 'Naïve softmax divides by zero when all inputs are equal. Subtracting max ensures the denominator is always at least 1.', 'correct' => false],
        ['text' => 'f32 cannot represent probabilities below 10⁻⁷. Subtracting max re-scales the distribution to avoid underflow.', 'correct' => false],
        ['text' => 'exp() is undefined for negative inputs. Subtracting max makes all inputs positive.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Can you broadcast tensors of shapes [8, 12, 128, 64] and [12, 1, 64]?',
      'answers' => [
        ['text' => 'Yes — align from right: [8,12,128,64] vs [12,1,64]. The last 3 dims are compatible (12=12, 128 vs 1 → expand to 128, 64=64). The leading 8 is fine.', 'correct' => true, 'explanation' => 'Broadcasting aligns from the right. Dimensions must be equal or one must be 1. [12,1,64] broadcasts to [8,12,128,64] because the missing leading dimension is implicitly 1, and the size-1 second-to-last dimension broadcasts to 128.'],
        ['text' => 'No — the shapes have different numbers of dimensions, which broadcasting cannot handle.', 'correct' => false],
        ['text' => 'No — the 64 dimension appears in both tensors but in different positions, causing a shape conflict.', 'correct' => false],
        ['text' => 'Yes, but only if the batch size 8 is explicitly prepended to the second tensor as [8,12,1,64].', 'correct' => false],
      ],
    ],
    [
      'question' => 'After applying masked fill with −∞ to future positions and then softmax, what value do those positions have?',
      'answers' => [
        ['text' => 'Exactly 0.0 — softmax(−∞) = exp(−∞)/sum = 0/sum = 0.', 'correct' => true, 'explanation' => 'exp(−∞) = 0. Divided by any positive sum, the result is 0. So masked future positions contribute nothing to the attended value vector.'],
        ['text' => 'A very small positive value (epsilon), because softmax cannot produce exact zeros.', 'correct' => false],
        ['text' => '−∞, because softmax preserves the ordering of inputs.', 'correct' => false],
        ['text' => '1/(seq_len) — softmax distributes probability equally across masked positions.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 7 ──────────────────────────────────────────────────────────────
7 => [
  'title'   => 'Transformer Architecture and Embeddings',
  'module'  => 3,
  'objectives' => [
    'Describe the transformer\'s high-level forward pass from tokens to logits.',
    'Explain the difference between token embeddings and position embeddings.',
    'Implement layer normalization and explain why it stabilizes training.',
  ],
  'content_html' => <<<'HTML'
<h2>What Is a Transformer?</h2>
<p>A transformer is a sequence-to-sequence neural network built entirely from attention and feedforward layers. Unlike recurrent networks (RNN, LSTM), transformers process the entire sequence in parallel — every position attends to every other position simultaneously. This parallelism makes transformers fast on modern hardware but requires a fixed maximum context length.</p>
<p>Feste implements a decoder-only transformer (like GPT-2): given a sequence of tokens, predict the next token. The architecture is:</p>
<pre><code>Input tokens (integers)
    ↓  token embedding lookup
    ↓  + position embedding lookup
    ↓  (dropout)
    ↓  N × TransformerBlock
         ↓  LayerNorm
         ↓  Multi-Head Self-Attention
         ↓  residual add
         ↓  LayerNorm
         ↓  MLP (feedforward)
         ↓  residual add
    ↓  final LayerNorm
    ↓  linear projection to vocabulary size
Output logits (one per vocabulary token)
</code></pre>

<h2>The Forward Pass</h2>
<p>In Rust, the model's <code>forward</code> function takes a tensor of shape <code>[batch_size, seq_len]</code> (token IDs) and returns <code>[batch_size, seq_len, vocab_size]</code> (logits):</p>
<pre><code class="language-rust">pub fn forward(&amp;self, tokens: &amp;Tensor) -&gt; Tensor {
    let tok_emb = self.token_embedding.forward(tokens);   // [B, T, C]
    let pos_emb = self.pos_embedding.forward_positions(tokens.shape[1]); // [T, C]
    let mut x = tok_emb.add(&amp;pos_emb);                   // [B, T, C]
    x = self.emb_dropout.forward(&amp;x);
    for block in &amp;self.blocks {
        x = block.forward(&amp;x);
    }
    x = self.ln_final.forward(&amp;x);
    self.lm_head.forward(&amp;x)                             // [B, T, vocab_size]
}
</code></pre>

<h2>Embeddings: From IDs to Vectors</h2>
<p>Token embeddings are a lookup table of shape <code>[vocab_size, embed_dim]</code>. Given a token ID, we extract the corresponding row: a vector of <code>embed_dim</code> floats. This vector is the model's learned representation of that token.</p>
<p>The embedding is initialized randomly and updated by backpropagation during training. After training, similar tokens (semantically or syntactically) end up with similar embedding vectors.</p>
<pre><code class="language-rust">pub struct TokenEmbedding {
    weight: Tensor,  // shape: [vocab_size, embed_dim]
}

impl TokenEmbedding {
    pub fn forward(&amp;self, token_ids: &amp;Tensor) -&gt; Tensor {
        // Index rows of weight by token IDs
        self.weight.index_select(0, token_ids)
    }
}
</code></pre>

<h2>Position Embeddings</h2>
<p>Self-attention is permutation-invariant: without additional information, the model cannot distinguish "the cat sat" from "sat cat the." Position embeddings inject the order of tokens.</p>
<p>Feste uses <strong>learned position embeddings</strong>: a second lookup table of shape <code>[max_seq_len, embed_dim]</code>. Position 0 contributes embedding vector 0, position 1 contributes embedding vector 1, etc. These are summed with the token embeddings:</p>
<pre><code class="language-rust">pub fn forward_positions(&amp;self, seq_len: usize) -&gt; Tensor {
    // Extract rows 0..seq_len from the position embedding table
    let positions = Tensor::arange(seq_len as f32);
    self.weight.index_select(0, &amp;positions)
}
</code></pre>
<p><em>Alternative</em>: sinusoidal position encodings (fixed, not learned) from the original "Attention Is All You Need" paper. Feste uses learned embeddings because they often perform better on the specific context lengths used in training.</p>

<h2>Layer Normalization</h2>
<p>Layer normalization normalizes each token's embedding vector to have mean 0 and standard deviation 1, then applies learned scale (γ) and shift (β) parameters:</p>
<pre><code>LayerNorm(x) = γ * (x - mean(x)) / sqrt(var(x) + ε) + β
</code></pre>
<p>Applied before each attention and MLP block (pre-norm), this stabilizes training by preventing activations from growing or shrinking across many layers.</p>
<pre><code class="language-rust">pub fn forward(&amp;self, x: &amp;Tensor) -&gt; Tensor {
    let mean = x.mean_dim(-1, true);      // [B, T, 1]
    let var  = x.var_dim(-1, true);       // [B, T, 1]
    let normalized = (x - &amp;mean) / (&amp;var + 1e-5_f32).sqrt();
    &amp;self.gamma * &amp;normalized + &amp;self.beta
}
</code></pre>
<p><strong>Why not batch normalization?</strong> Batch norm normalizes across the batch dimension, which is problematic for variable-length sequences and small batch sizes. Layer norm normalizes across the feature dimension (each token independently), which works well regardless of batch size or sequence length.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>The transformer forward pass: embed → position embed → N blocks of (LayerNorm + Attention + LayerNorm + MLP) → project to vocab.</li>
  <li>Token embeddings are a learned lookup table; the model learns which vectors represent which concepts.</li>
  <li>Position embeddings (learned or sinusoidal) provide sequence order information to the permutation-invariant attention.</li>
  <li>Layer normalization stabilizes activations per-token, making deep transformers trainable.</li>
</ul>
<p><em>Adapted from Part 3 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Decoder-only transformer: embed tokens → N attention+MLP blocks → project to logits.',
    'Token embeddings map token IDs to dense vectors; they are learned during training.',
    'Position embeddings (learned or sinusoidal) give the permutation-invariant attention a sense of order.',
    'Layer normalization per-token (not per-batch) keeps activations stable across many layers.',
  ],
  'quiz' => [
    [
      'question' => 'Why do transformers require position embeddings when RNNs do not?',
      'answers' => [
        ['text' => 'Self-attention is permutation-invariant — it computes the same output regardless of token order. Position embeddings break this symmetry by injecting sequence position information.', 'correct' => true, 'explanation' => 'An RNN processes tokens one at a time in order, so order is implicit in the recurrent state. Attention sees all positions at once and treats them symmetrically without position information.'],
        ['text' => 'Transformers use larger embedding dimensions than RNNs, requiring extra dimensions for positional data.', 'correct' => false],
        ['text' => 'Position embeddings enable the model to use multiple CPU cores, one per position.', 'correct' => false],
        ['text' => 'Transformers lack recurrence, so they use position embeddings as a substitute for the hidden state.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the shape of the token embedding lookup table in a model with vocabulary size 10,000 and embedding dimension 512?',
      'answers' => [
        ['text' => '[10000, 512] — one 512-dimensional vector per vocabulary entry.', 'correct' => true, 'explanation' => 'The embedding table stores one vector per token in the vocabulary. Looking up token ID 42 returns row 42: a vector of 512 floats.'],
        ['text' => '[512, 10000] — the embedding is transposed to facilitate the output projection.', 'correct' => false],
        ['text' => '[batch_size, seq_len, 512] — the table is pre-expanded for the current batch.', 'correct' => false],
        ['text' => '[10000] — one scalar per token, later expanded to 512 dimensions by a linear layer.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Layer normalization is applied before each attention and MLP block in Feste (pre-norm). What problem does this solve?',
      'answers' => [
        ['text' => 'Without normalization, activations can grow or shrink exponentially across many layers, making gradients explode or vanish and training unstable.', 'correct' => true, 'explanation' => 'In deep networks, small deviations in scale compound across layers. Pre-norm ensures activations entering each block have consistent scale, stabilizing the gradient flow that backpropagation relies on.'],
        ['text' => 'Pre-norm prevents the model from memorizing training data by adding noise before each layer.', 'correct' => false],
        ['text' => 'LayerNorm converts the transformer into a recurrent network by normalizing the hidden state.', 'correct' => false],
        ['text' => 'Pre-norm reduces the model\'s parameter count by sharing normalization weights across layers.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 8 ──────────────────────────────────────────────────────────────
8 => [
  'title'   => 'Multi-Head Self-Attention',
  'module'  => 3,
  'objectives' => [
    'Explain the query, key, value roles in scaled dot-product attention.',
    'Implement causal (masked) multi-head attention from first principles.',
    'Describe why multiple attention heads are better than one large head.',
  ],
  'content_html' => <<<'HTML'
<h2>The Attention Mechanism</h2>
<p>Attention is the core operation that allows every token to "look at" every other token and gather information from it. Conceptually:</p>
<ul>
  <li><strong>Query (Q):</strong> What is this position looking for?</li>
  <li><strong>Key (K):</strong> What does each position offer?</li>
  <li><strong>Value (V):</strong> What information does each position contribute if attended to?</li>
</ul>
<p>The attention score between position i (query) and position j (key) is the dot product Q[i] · K[j], scaled by √(head_dim) to prevent large values from pushing softmax into saturation:</p>
<pre><code>Attention(Q, K, V) = softmax(QKᵀ / √dₖ) · V
</code></pre>
<p>For a sequence of length T with head dimension dₖ:</p>
<ul>
  <li>Q, K, V each have shape <code>[T, dₖ]</code></li>
  <li>QKᵀ has shape <code>[T, T]</code> — the attention score matrix</li>
  <li>After softmax, each row sums to 1.0 (a probability distribution over positions)</li>
  <li>The output has shape <code>[T, dₖ]</code> — a weighted sum of V vectors</li>
</ul>

<h2>Causal Masking</h2>
<p>In a language model, position <em>i</em> must not attend to positions <em>j &gt; i</em>. We enforce this with a lower-triangular mask: before softmax, set all future positions to −∞.</p>
<pre><code class="language-rust">// Build causal mask: mask[i,j] = false when j > i
fn causal_mask(seq_len: usize) -&gt; Tensor {
    let mut mask = Tensor::zeros(&amp;[seq_len, seq_len]);
    for i in 0..seq_len {
        for j in (i + 1)..seq_len {
            mask[[i, j]] = f32::NEG_INFINITY;
        }
    }
    mask
}
</code></pre>

<h2>Multi-Head Attention</h2>
<p>Instead of one large attention operation with dimension <code>embed_dim</code>, we run <code>n_heads</code> parallel attention operations, each with dimension <code>head_dim = embed_dim / n_heads</code>. The results are concatenated and projected back to <code>embed_dim</code>:</p>
<pre><code class="language-rust">pub fn forward(&amp;self, x: &amp;Tensor) -&gt; Tensor {
    let (b, t, c) = (x.shape[0], x.shape[1], x.shape[2]);
    let h = self.n_heads;
    let dh = c / h;  // head_dim

    // Project to Q, K, V — each shape [B, T, C]
    let q = self.q_proj.forward(x);
    let k = self.k_proj.forward(x);
    let v = self.v_proj.forward(x);

    // Reshape to [B, h, T, dh]
    let q = q.reshape(&amp;[b, t, h, dh]).transpose(1, 2);
    let k = k.reshape(&amp;[b, t, h, dh]).transpose(1, 2);
    let v = v.reshape(&amp;[b, t, h, dh]).transpose(1, 2);

    // Scaled dot-product attention per head
    let scale = (dh as f32).sqrt();
    let scores = q.matmul(&amp;k.transpose(2, 3)) / scale;  // [B, h, T, T]

    // Apply causal mask
    let mask = self.causal_mask.slice(t);
    let scores = scores + &amp;mask;  // broadcast mask over batch and heads

    let weights = scores.softmax(-1);                    // [B, h, T, T]
    let weights = self.attn_dropout.forward(&amp;weights);

    let out = weights.matmul(&amp;v);                       // [B, h, T, dh]

    // Recombine heads: [B, T, C]
    let out = out.transpose(1, 2).reshape(&amp;[b, t, c]);
    self.out_proj.forward(&amp;out)
}
</code></pre>

<h2>Why Multiple Heads?</h2>
<p>Different attention heads learn to attend to different kinds of relationships simultaneously:</p>
<ul>
  <li>One head might focus on grammatical agreement (verb→subject).</li>
  <li>Another might track coreference (pronoun→noun).</li>
  <li>Another might attend to positional proximity.</li>
</ul>
<p>Using 12 heads of 64 dimensions each (total 768 dimensions) lets the model learn 12 independent "views" of the sequence simultaneously, which is empirically better than one 768-dimensional head. We'll revisit this tradeoff in Module 5's architecture experiments.</p>

<h2>Attention Complexity</h2>
<p>The QKᵀ matrix multiply is O(T² × C) — quadratic in sequence length. This is the fundamental scaling bottleneck of transformers: doubling the context length quadruples the attention computation and memory. For Feste's context of 256 tokens this is fine; for modern LLMs with 128K+ context, it requires specialized attention algorithms (FlashAttention, sliding window, etc.).</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Q, K, V projections map each token to a query, key, and value vector; attention scores are dot products of queries against all keys.</li>
  <li>Scaling by √dₖ prevents softmax saturation when head dimension is large.</li>
  <li>Causal masking (−∞ before softmax) blocks attention to future positions.</li>
  <li>Multiple heads learn independent attention patterns; concatenated output captures diverse relationships.</li>
  <li>Attention is O(T²) in sequence length — the fundamental scaling bottleneck.</li>
</ul>
<p><em>Adapted from Part 3 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Q·Kᵀ / √dₖ gives attention scores; softmax converts them to weights; weighted sum of V is the output.',
    'Causal mask sets future positions to −∞ before softmax → 0 weight on future tokens.',
    'Multi-head: h parallel smaller attentions, concatenated → each head learns different patterns.',
    'Attention is O(T²) in sequence length — the core scaling bottleneck of transformers.',
  ],
  'quiz' => [
    [
      'question' => 'Why is the dot product between queries and keys divided by √dₖ?',
      'answers' => [
        ['text' => 'Dot products grow proportionally to the dimension dₖ; dividing by √dₖ keeps variance ~1 so softmax does not saturate into very peaked distributions.', 'correct' => true, 'explanation' => 'If Q and K have elements drawn from N(0,1), their dot product has variance dₖ. Dividing by √dₖ restores variance to 1. Without this, large dₖ causes the softmax to output near-one-hot distributions (the model only attends to one position).'],
        ['text' => 'Division by √dₖ normalizes the keys to unit vectors, making cosine similarity equivalent to dot product.', 'correct' => false],
        ['text' => 'It is a regularization technique to prevent the attention weights from exceeding 1.0.', 'correct' => false],
        ['text' => 'The square root converts the attention scores to log probabilities for numerical stability.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the computational complexity of computing the full QKᵀ attention score matrix for a sequence of length T?',
      'answers' => [
        ['text' => 'O(T² × dₖ) — each of the T² score entries requires a dot product over dₖ dimensions.', 'correct' => true, 'explanation' => 'The score matrix is [T, T]. Each entry is the dot product of a T-dimensional query with a T-dimensional key, taking O(dₖ) operations. Total: T × T × dₖ = O(T²dₖ).'],
        ['text' => 'O(T × dₖ) — attention processes each query against all keys in a single pass.', 'correct' => false],
        ['text' => 'O(T log T) — self-attention can be computed with a fast Fourier transform.', 'correct' => false],
        ['text' => 'O(dₖ²) — complexity depends only on head dimension, not sequence length.', 'correct' => false],
      ],
    ],
    [
      'question' => 'A model uses 8 attention heads with head_dim=64. What is embed_dim, and why might 8 heads outperform 1 head of dimension 512?',
      'answers' => [
        ['text' => 'embed_dim = 512. Eight heads learn 8 different attention patterns simultaneously; one 512-dim head is constrained to a single pattern, limiting expressiveness.', 'correct' => true, 'explanation' => 'Multi-head attention can learn syntactic, semantic, and positional patterns simultaneously in parallel heads. A single large head must blend all these patterns into one, which empirically performs worse.'],
        ['text' => 'embed_dim = 64. Each head sees the full embed_dim and competition between heads improves accuracy.', 'correct' => false],
        ['text' => 'embed_dim = 8 × 512 = 4096. More parameters always improve performance.', 'correct' => false],
        ['text' => 'embed_dim = 512, but 8 heads are faster because they run on 8 separate CPU cores.', 'correct' => false],
      ],
    ],
    [
      'question' => 'In the attention weight matrix after softmax, what does row i represent?',
      'answers' => [
        ['text' => 'A probability distribution over all (non-masked) positions — how much position i attends to each other position.', 'correct' => true, 'explanation' => 'Row i of the softmax output sums to 1.0 and represents position i\'s attention distribution. Entry [i,j] tells us how much of position j\'s value vector is incorporated into position i\'s output.'],
        ['text' => 'The value vector for position i, already scaled by attention weights.', 'correct' => false],
        ['text' => 'The gradient of the loss with respect to the i-th token\'s embedding.', 'correct' => false],
        ['text' => 'The cosine similarity between position i and all other positions.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 9 ──────────────────────────────────────────────────────────────
9 => [
  'title'   => 'The Complete Forward Pass',
  'module'  => 3,
  'objectives' => [
    'Describe the MLP layer\'s role as a per-position memory store.',
    'Trace the full forward pass through a transformer block with residual connections.',
    'Estimate parameter counts for different model size configurations.',
  ],
  'content_html' => <<<'HTML'
<h2>The MLP Layer</h2>
<p>After self-attention, each transformer block applies a feedforward MLP to each position independently. The MLP is typically two linear layers with a GELU activation:</p>
<pre><code>MLP(x) = Linear(GELU(Linear(x, 4C → C)), C → C)
</code></pre>
<p>The expansion factor is 4×: the first linear layer expands from <code>embed_dim</code> (C) to <code>4×C</code>, then the second contracts back to C. For embed_dim=512, the MLP has hidden dimension 2048.</p>
<pre><code class="language-rust">pub fn forward(&amp;self, x: &amp;Tensor) -&gt; Tensor {
    let h = self.fc1.forward(x);       // [B, T, 4C]
    let h = h.gelu();                  // element-wise GELU
    let h = self.mlp_dropout.forward(&amp;h);
    self.fc2.forward(&amp;h)               // [B, T, C]
}
</code></pre>
<p>GELU (Gaussian Error Linear Unit) is the standard nonlinearity for transformers. Unlike ReLU which hard-zeros negative inputs, GELU is a smooth function that allows small negative values through. This smoothness makes gradients flow better in deep networks.</p>

<h2>Why the MLP Matters</h2>
<p>Attention aggregates information across positions. The MLP then processes each position's aggregated representation independently. Research suggests the MLP layers act as "key-value memories" — they store factual associations learned during training. Much of a model's world knowledge is stored in MLP weights, not in attention patterns.</p>

<h2>The Complete Transformer Block</h2>
<p>Each block applies attention and MLP with residual connections and pre-LayerNorm:</p>
<pre><code class="language-rust">pub fn forward(&amp;self, x: &amp;Tensor) -&gt; Tensor {
    // Attention sub-block with residual
    let normed = self.ln1.forward(x);
    let attn_out = self.attention.forward(&amp;normed);
    let x = x.add(&amp;attn_out);   // residual connection

    // MLP sub-block with residual
    let normed = self.ln2.forward(&amp;x);
    let mlp_out = self.mlp.forward(&amp;normed);
    x.add(&amp;mlp_out)             // residual connection
}
</code></pre>
<p><strong>Residual connections</strong> are crucial. They add the block's input directly to its output, creating a "highway" for gradients to flow straight through without passing through the attention or MLP weights. This prevents the vanishing gradient problem in deep networks and means the model is initialized close to an identity function (early in training, layers learn small corrections to the identity rather than complete transformations).</p>

<h2>Model Sizes</h2>
<p>The key hyperparameters and their interactions:</p>
<table>
<thead><tr><th>Size</th><th>embed_dim</th><th>n_layers</th><th>n_heads</th><th>~Parameters</th></tr></thead>
<tbody>
<tr><td>Feste "Pocket Bard"</td><td>512</td><td>6</td><td>8</td><td>~9M</td></tr>
<tr><td>GPT-2 small</td><td>768</td><td>12</td><td>12</td><td>117M</td></tr>
<tr><td>GPT-2 medium</td><td>1024</td><td>24</td><td>16</td><td>345M</td></tr>
<tr><td>GPT-2 large</td><td>1280</td><td>36</td><td>20</td><td>774M</td></tr>
</tbody>
</table>
<p>Parameter count breakdown for Feste (512 embed, 6 layers, vocab 10,000):</p>
<ul>
  <li>Token embedding table: 10,000 × 512 = 5.1M</li>
  <li>Position embedding table: 256 × 512 = 0.13M</li>
  <li>Per layer attention (Q, K, V, out projections): 4 × (512 × 512) = 1.05M × 6 = 6.3M</li>
  <li>Per layer MLP (fc1 + fc2): 2 × (512 × 2048) = 2.1M × 6 = 12.6M</li>
  <li>LayerNorm parameters: small (&lt;0.1M)</li>
  <li><strong>Total: ~24M</strong> (the actual Pocket Bard is ~9M with smaller dimensions)</li>
</ul>

<h2>Key Takeaways</h2>
<ul>
  <li>The MLP applies position-wise: each token is processed independently after attention aggregates information across the sequence.</li>
  <li>GELU is a smooth nonlinearity preferred over ReLU in transformers due to better gradient flow.</li>
  <li>Residual connections create gradient highways through deep networks and initialize near identity.</li>
  <li>Parameters scale with embed_dim² (linear layers) and with n_layers × (attention + MLP).</li>
</ul>
<p><em>Adapted from Part 3 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'MLP: expand to 4×embed_dim with GELU, project back — each position processed independently.',
    'MLP layers act as factual memory; attention aggregates context then MLP refines it.',
    'Residual connections add input to output — gradient highways that prevent vanishing gradients.',
    'Parameters scale as embed_dim² per layer; total ~= 12 × embed_dim² × n_layers (rule of thumb).',
  ],
  'quiz' => [
    [
      'question' => 'The MLP inside a transformer block processes each token position independently. What role does this separation serve?',
      'answers' => [
        ['text' => 'Attention gathers context from across the sequence; the MLP then refines each position\'s representation using that context, acting like a per-token memory/computation unit.', 'correct' => true, 'explanation' => 'The two sub-layers are complementary: attention mixes information across positions, then MLP processes each mixed representation independently. This separation is key to the transformer\'s expressiveness.'],
        ['text' => 'Processing positions independently saves memory by avoiding the T×T attention matrix.', 'correct' => false],
        ['text' => 'The MLP independently generates the next token; attention is only used during training.', 'correct' => false],
        ['text' => 'Independent position processing allows different GPU cores to handle different tokens.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the function of the residual connection in each transformer block?',
      'answers' => [
        ['text' => 'It adds the block\'s input directly to its output, giving gradients a direct path backward through the network and initializing each block near an identity function.', 'correct' => true, 'explanation' => 'output = input + F(input). Gradient of loss w.r.t. input = gradient w.r.t. output × (1 + ∂F/∂input). The +1 means gradients flow straight through even when ∂F/∂input is small, preventing vanishing gradients.'],
        ['text' => 'It passes the output of attention directly to the output layer, bypassing the MLP for speed.', 'correct' => false],
        ['text' => 'It prevents the model from memorizing training data by adding a noise residual.', 'correct' => false],
        ['text' => 'It normalizes the output of each sub-layer to unit norm before the next layer.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Doubling embed_dim from 512 to 1024 while keeping n_layers constant approximately multiplies parameter count by:',
      'answers' => [
        ['text' => '4× — because linear layer parameters scale as embed_dim², so 1024² / 512² = 4.', 'correct' => true, 'explanation' => 'Each weight matrix in attention and MLP has dimensions proportional to embed_dim × embed_dim. Doubling one side of all matrices gives 4× more parameters in those layers.'],
        ['text' => '2× — parameters scale linearly with embed_dim.', 'correct' => false],
        ['text' => '8× — because both attention AND MLP scale quadratically with embed_dim.', 'correct' => false],
        ['text' => '16× — parameter count includes activations which also scale with embed_dim.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 10 ──────────────────────────────────────────────────────────────
10 => [
  'title'   => 'Backpropagation — The Backward Pass',
  'module'  => 4,
  'objectives' => [
    'Explain what backpropagation computes and why it is necessary for training.',
    'Apply the chain rule to derive gradients through a linear layer.',
    'Describe the gradient flow through attention and residual connections.',
  ],
  'content_html' => <<<'HTML'
<h2>What Training Does</h2>
<p>Training a neural network means finding weights that minimize a loss function. For a language model, the loss is cross-entropy: how well do the model's predicted probabilities match the actual next tokens?</p>
<pre><code>Loss = -1/T × Σ log(p[t, token[t+1]])
</code></pre>
<p>Where <code>p[t, token[t+1]]</code> is the probability the model assigns to the correct next token at position t.</p>
<p>To minimize this loss, we need to know: for each weight w, does increasing w increase or decrease the loss? This is the gradient ∂Loss/∂w. Backpropagation computes all these gradients efficiently using the chain rule.</p>

<h2>The Chain Rule</h2>
<p>If Loss = f(g(w)), then ∂Loss/∂w = (∂f/∂g) × (∂g/∂w). Backpropagation applies this rule through every layer from the output back to the inputs.</p>
<p>For a linear layer y = Wx + b:</p>
<ul>
  <li>Given gradient ∂Loss/∂y from the layer above (the "upstream gradient")</li>
  <li>Gradient w.r.t. weights: ∂Loss/∂W = (∂Loss/∂y) × xᵀ</li>
  <li>Gradient w.r.t. bias: ∂Loss/∂b = sum of ∂Loss/∂y over the batch</li>
  <li>Gradient to pass down: ∂Loss/∂x = Wᵀ × (∂Loss/∂y)</li>
</ul>
<pre><code class="language-rust">pub fn backward(&amp;mut self, upstream_grad: &amp;Tensor, input: &amp;Tensor) -&gt; Tensor {
    // Accumulate weight gradient
    self.weight_grad = upstream_grad.transpose(0, 1).matmul(input);
    // Accumulate bias gradient (sum over batch)
    self.bias_grad = upstream_grad.sum_dim(0, false);
    // Return gradient to pass to previous layer
    upstream_grad.matmul(&amp;self.weight.transpose(0, 1))
}
</code></pre>

<h2>Loss Computation and Gradient</h2>
<p>Cross-entropy loss with softmax has a clean gradient. If the model outputs logits z (before softmax) and the correct class is c:</p>
<pre><code>∂Loss/∂z[i] = softmax(z)[i] - 1{i == c}
</code></pre>
<p>For correct class c: gradient = (predicted_prob - 1). For all other classes: gradient = predicted_prob. The gradient is zero only when the model predicts the correct class with probability 1.0.</p>

<h2>Layer Normalization Backward Pass</h2>
<p>LayerNorm requires computing gradients through the normalization operation:</p>
<pre><code>y = γ × (x - μ) / σ + β

∂Loss/∂γ = Σ upstream_grad × (x - μ) / σ
∂Loss/∂β = Σ upstream_grad
∂Loss/∂x = (1/σ) × [upstream_grad - mean(upstream_grad) - norm_x × mean(upstream_grad × norm_x)] × γ
</code></pre>
<p>The mean terms exist because the normalization of one element depends on all elements in the same layer — a classic example of non-local gradient dependencies.</p>

<h2>GELU Backward Pass</h2>
<p>GELU does not have a simple derivative like ReLU (which is just 0 or 1). Feste approximates GELU with the tanh formula and computes its derivative analytically:</p>
<pre><code class="language-rust">fn gelu_backward(x: f32, upstream: f32) -&gt; f32 {
    let c = 0.7978845608_f32;  // sqrt(2/pi)
    let t = (c * (x + 0.044715 * x.powi(3))).tanh();
    let d_gelu = 0.5 * (1.0 + t) + 0.5 * x * (1.0 - t * t)
                 * c * (1.0 + 3.0 * 0.044715 * x.powi(2));
    upstream * d_gelu
}
</code></pre>

<h2>Attention Backward Pass</h2>
<p>Attention requires backpropagating through the softmax-weighted sum. The key steps:</p>
<ol>
  <li>Gradient through V: ∂Loss/∂V = weightsᵀ × ∂Loss/∂output</li>
  <li>Gradient through weights (pre-softmax): ∂Loss/∂scores = softmax_backward(∂Loss/∂weights)</li>
  <li>Gradient through Q: ∂Loss/∂Q = ∂Loss/∂scores × K</li>
  <li>Gradient through K: ∂Loss/∂K = ∂Loss/∂scores × Q</li>
</ol>
<p>Softmax backward involves the Jacobian of the softmax function, but it simplifies to:</p>
<pre><code>∂Loss/∂z[i] = w[i] × (∂Loss/∂w[i] - Σ w[j] × ∂Loss/∂w[j])
</code></pre>

<h2>Residual Connections</h2>
<p>The residual connection y = x + F(x) has a trivially simple backward pass: the upstream gradient flows to both x and F(x) unchanged. This is why residual connections are so important for training deep networks — gradients pass through them with no attenuation.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Backprop applies the chain rule layer-by-layer from output to input, accumulating gradients w.r.t. every weight.</li>
  <li>Cross-entropy + softmax has a clean gradient: (predicted − 1_correct_class).</li>
  <li>LayerNorm backward is non-local: each input's gradient depends on all inputs in the same layer.</li>
  <li>Residual connections pass gradients through unchanged — no attenuation.</li>
</ul>
<p><em>Adapted from Part 4 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Backpropagation applies chain rule from loss to each weight: ∂Loss/∂w for every parameter.',
    'Cross-entropy softmax gradient: predicted_prob − 1 for correct class, predicted_prob for others.',
    'LayerNorm backward is non-local: normalizing one value depends on all values in the layer.',
    'Residual connections forward: y = x + F(x); backward: gradient copies unchanged to both branches.',
  ],
  'quiz' => [
    [
      'question' => 'For a linear layer y = Wx, given the upstream gradient ∂Loss/∂y, what is the gradient ∂Loss/∂W?',
      'answers' => [
        ['text' => '(∂Loss/∂y)ᵀ × x — the outer product of the upstream gradient and the input.', 'correct' => true, 'explanation' => 'By the chain rule, ∂(Wx)[i]/∂W[i,j] = x[j]. So ∂Loss/∂W[i,j] = (∂Loss/∂y)[i] × x[j], which is the outer product of ∂Loss/∂y and x.'],
        ['text' => 'W × (∂Loss/∂y) — multiply the weight matrix by the upstream gradient.', 'correct' => false],
        ['text' => 'x × (∂Loss/∂y) — multiply the input by the upstream gradient.', 'correct' => false],
        ['text' => '(∂Loss/∂y) / x — the upstream gradient divided by the input.', 'correct' => false],
      ],
    ],
    [
      'question' => 'The cross-entropy softmax gradient for an incorrect class k is predicted_prob[k]. What does this imply about training?',
      'answers' => [
        ['text' => 'The model is penalized for assigning any probability to wrong classes — the gradient pushes probability mass toward the correct answer and away from all others.', 'correct' => true, 'explanation' => 'Gradient for incorrect class k is positive (predicted_prob[k] > 0), meaning increasing the logit for k increases loss. Training reduces these logits. Gradient for correct class c is (predicted_prob[c] − 1) < 0, so increasing logit for c decreases loss.'],
        ['text' => 'Only the correct class contributes to the gradient; all other gradients are zero.', 'correct' => false],
        ['text' => 'The model learns by maximizing probability for incorrect classes, which forces redistribution to the correct class.', 'correct' => false],
        ['text' => 'The gradient for incorrect classes is always negative, pushing their probabilities toward 1.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Why do residual connections (y = x + F(x)) help with training deep networks?',
      'answers' => [
        ['text' => 'The gradient ∂Loss/∂x includes a +1 term (from the identity path), preventing it from vanishing even when ∂F/∂x is very small.', 'correct' => true, 'explanation' => 'Without residuals, gradients in a 24-layer network multiply through 24 Jacobians — if each is slightly less than 1, the product vanishes. With residuals, each Jacobian has an additive 1, so gradients have a clear path through.'],
        ['text' => 'Residual connections share weights between layers, reducing the total number of parameters that need gradients.', 'correct' => false],
        ['text' => 'They prevent the loss from increasing during training by clipping the gradient norm.', 'correct' => false],
        ['text' => 'Residuals allow parallel gradient computation across all layers simultaneously.', 'correct' => false],
      ],
    ],
  ],
],

  ]; // end return
} // end function
