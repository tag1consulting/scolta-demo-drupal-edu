<?php
/**
 * Lesson data: Modules 4–5 (Lessons 11–15).
 * Adapted from Parts 4–5 of "Building an LLM From Scratch in Rust" by Jeremy Andrews.
 */
function lessons_11_15(): array {
  return [

// ─── Lesson 11 ──────────────────────────────────────────────────────────────
11 => [
  'title'   => 'Optimizers and Regularization',
  'module'  => 4,
  'objectives' => [
    'Explain AdamW\'s moment estimates and why it outperforms plain SGD for transformers.',
    'Implement gradient clipping and describe when and why it is necessary.',
    'Describe how dropout regularization prevents overfitting during training.',
  ],
  'content_html' => <<<'HTML'
<h2>The AdamW Optimizer</h2>
<p>Adam (Adaptive Moment Estimation) tracks two exponentially decaying averages for each parameter:</p>
<ul>
  <li><strong>m</strong> (first moment): the moving average of gradients — "which direction has this weight been pushed recently?"</li>
  <li><strong>v</strong> (second moment): the moving average of squared gradients — "how noisy is this weight's gradient?"</li>
</ul>
<p>The update rule:</p>
<pre><code>m = β₁ × m + (1 − β₁) × g      # update first moment (β₁ = 0.9)
v = β₂ × v + (1 − β₂) × g²     # update second moment (β₂ = 0.999)
m̂ = m / (1 − β₁^t)             # bias correction
v̂ = v / (1 − β₂^t)             # bias correction
w = w − lr × m̂ / (√v̂ + ε)    # weight update
</code></pre>
<p>The division by √v̂ gives each weight its own effective learning rate: if a weight's gradient is consistently large (large v̂), the update is small — it's already in a consistent direction. If the gradient is small or noisy, the update is scaled up. This adaptive per-parameter learning rate is why Adam converges much faster than SGD on sparse gradient problems like language modeling.</p>
<p><strong>AdamW vs Adam:</strong> AdamW separates weight decay from the gradient update. In plain Adam, L2 regularization gets absorbed into the gradient and scales with the adaptive rate, weakening it. AdamW applies weight decay directly to weights before the Adam update, making regularization consistent regardless of gradient magnitude:</p>
<pre><code class="language-rust">pub fn step(&amp;mut self) {
    self.t += 1;
    let bc1 = 1.0 - self.beta1.powi(self.t as i32);
    let bc2 = 1.0 - self.beta2.powi(self.t as i32);

    for (param, grad, m, v) in self.params_iter_mut() {
        // AdamW weight decay (applied directly, not through gradient)
        *param *= 1.0 - self.lr * self.weight_decay;

        // Adam moment updates
        *m = self.beta1 * *m + (1.0 - self.beta1) * grad;
        *v = self.beta2 * *v + (1.0 - self.beta2) * grad * grad;

        let m_hat = *m / bc1;
        let v_hat = *v / bc2;
        *param -= self.lr * m_hat / (v_hat.sqrt() + self.eps);
    }
}
</code></pre>
<p>Typical hyperparameters: lr=3e-4, β₁=0.9, β₂=0.999, ε=1e-8, weight_decay=0.1.</p>

<h2>Gradient Clipping</h2>
<p>Occasionally (especially early in training or after a difficult batch), the gradient norm spikes dramatically. A single large gradient update can destabilize training irreversibly — "gradient explosion." Gradient clipping prevents this by scaling down the entire gradient vector when its L2 norm exceeds a threshold:</p>
<pre><code class="language-rust">pub fn clip_grad_norm(&amp;mut self, max_norm: f32) {
    let total_norm: f32 = self.params
        .iter()
        .map(|p| p.grad.iter().map(|g| g * g).sum::&lt;f32&gt;())
        .sum::&lt;f32&gt;()
        .sqrt();

    if total_norm &gt; max_norm {
        let scale = max_norm / total_norm;
        for p in &amp;mut self.params {
            for g in &amp;mut p.grad { *g *= scale; }
        }
    }
}
</code></pre>
<p>The key: clipping the global norm scales all gradients uniformly, preserving gradient direction while bounding the step size. A typical threshold is 1.0. Feste applies gradient clipping before the optimizer step.</p>

<h2>Dropout Regularization</h2>
<p>Dropout randomly zeros a fraction (typically 10–20%) of activations during training, forcing the network to not rely on any single neuron:</p>
<pre><code class="language-rust">pub fn forward(&amp;self, x: &amp;Tensor) -&gt; Tensor {
    if !self.training || self.p == 0.0 {
        return x.clone();
    }
    // Create a mask: keep each element with probability (1 - p)
    let mask: Vec&lt;f32&gt; = (0..x.data.len())
        .map(|_| if rand::random::&lt;f32&gt;() &gt; self.p { 1.0 / (1.0 - self.p) } else { 0.0 })
        .collect();
    x.mul_elementwise(&amp;Tensor::from_vec(mask, &amp;x.shape))
}
</code></pre>
<p>The <code>1.0 / (1.0 - p)</code> scaling ("inverted dropout") ensures the expected output remains unchanged — the surviving activations are scaled up to compensate. At inference time, dropout is disabled and no scaling is needed.</p>
<p>Feste uses dropout in three places: after embeddings, after attention weights, and after the MLP. Typical dropout rate: 0.1 (10%).</p>

<h2>Key Takeaways</h2>
<ul>
  <li>AdamW tracks per-weight gradient direction (m) and noise (v); the ratio m/√v gives an adaptive effective learning rate per weight.</li>
  <li>AdamW fixes a subtle Adam bug where weight decay interacts with adaptive rates; separate the decay step.</li>
  <li>Gradient clipping scales the entire gradient vector when its norm exceeds a threshold, preserving direction but bounding the step size.</li>
  <li>Dropout zeros random activations during training, forcing redundancy; inverted dropout scales survivors to maintain expected magnitude.</li>
</ul>
<p><em>Adapted from Part 4 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Adam\'s first moment tracks gradient direction; second moment tracks gradient variance; ratio gives adaptive per-weight learning rate.',
    'AdamW applies weight decay directly to weights, not through gradients — prevents decay from being suppressed by large adaptive rates.',
    'Gradient clipping scales all gradients down uniformly when global norm exceeds threshold.',
    'Dropout + inverted scaling: zeroed neurons are compensated by surviving ones to maintain expected output.',
  ],
  'quiz' => [
    [
      'question' => 'Why does AdamW apply weight decay directly to the weight rather than adding an L2 penalty to the gradient?',
      'answers' => [
        ['text' => 'In Adam, L2 gradient penalties get divided by √v̂ (the adaptive rate), which weakens the regularization for frequently-updated weights. AdamW applies a fixed proportional decay instead.', 'correct' => true, 'explanation' => 'Adam scales gradients by 1/√v̂. An L2 penalty gradient λw gets the same scaling, so it is effectively weaker when the gradient variance is high. AdamW bypasses this by decaying the weight directly.'],
        ['text' => 'Gradient-based weight decay causes gradients to become negative, breaking the Adam moment estimates.', 'correct' => false],
        ['text' => 'Applying decay directly is faster because it avoids an extra gradient accumulation step.', 'correct' => false],
        ['text' => 'AdamW weight decay only affects the bias terms; applying it via gradient would affect all parameters.', 'correct' => false],
      ],
    ],
    [
      'question' => 'What does gradient clipping preserve, and what does it bound?',
      'answers' => [
        ['text' => 'It preserves the gradient direction (the unit vector) and bounds the step size (the norm). All gradients are scaled uniformly.', 'correct' => true, 'explanation' => 'When the global gradient norm exceeds max_norm, we multiply all gradients by max_norm/total_norm. This is equivalent to projecting the gradient onto the sphere of radius max_norm — same direction, controlled magnitude.'],
        ['text' => 'It preserves the gradient magnitude and bounds the direction to a cone around the previous update.', 'correct' => false],
        ['text' => 'It clips individual gradient components to [−max_norm, +max_norm], bounding each separately.', 'correct' => false],
        ['text' => 'It preserves the gradient of the output layer and zeros gradients in earlier layers.', 'correct' => false],
      ],
    ],
    [
      'question' => 'During inference, Feste disables dropout. Why is "inverted dropout" (scaling by 1/(1-p) during training) necessary for this to work correctly?',
      'answers' => [
        ['text' => 'Without scaling, the expected activation magnitude during training is (1-p)× the inference magnitude, creating a distribution mismatch. Inverted dropout matches training and inference expectations.', 'correct' => true, 'explanation' => 'If p=0.1, 10% of activations are zeroed. Without scaling, the average output is 0.9× normal. During inference with no dropout, outputs are 1.0× normal. The model\'s downstream weights would be miscalibrated. Scaling by 1/0.9 during training fixes this.'],
        ['text' => 'Inverted dropout ensures that dropout can be applied to the gradient as well as the forward pass.', 'correct' => false],
        ['text' => 'Without inverted dropout, the model would memorize which neurons are active and ignore the rest.', 'correct' => false],
        ['text' => 'Scaling is needed so that the total number of active parameters remains constant across training steps.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 12 ──────────────────────────────────────────────────────────────
12 => [
  'title'   => 'Training Monitoring and Checkpointing',
  'module'  => 4,
  'objectives' => [
    'Interpret training and validation loss curves and perplexity.',
    'Describe a cosine learning rate schedule with warmup.',
    'Implement model checkpointing and understand when to load vs. discard a checkpoint.',
  ],
  'content_html' => <<<'HTML'
<h2>Training Metrics</h2>
<p>The two primary metrics during training are:</p>
<ul>
  <li><strong>Training loss:</strong> cross-entropy on the training batches. Decreases throughout training (the model is learning to fit the training data).</li>
  <li><strong>Validation loss:</strong> cross-entropy on a held-out portion of the corpus (Feste uses 10% of Shakespeare). Decreases initially, then flattens or rises when the model overfits.</li>
</ul>
<p><strong>Perplexity</strong> = exp(loss). A perplexity of 30 means the model is (roughly) as uncertain as if it were choosing uniformly among 30 tokens. Lower perplexity = better model. Initial perplexity equals vocabulary size (random guessing); trained Feste achieves ~92 perplexity from scratch, ~52 with transfer learning.</p>

<h2>Watching Samples, Not Just Numbers</h2>
<p>A crucial lesson from the Feste experiments: validation loss and sample quality can diverge. During fine-tuning on Shakespeare after TinyStories pre-training, the validation loss continued to decrease while sample quality peaked and then degraded — the model was overfitting to Shakespeare's style at the expense of coherence.</p>
<p>Feste periodically generates samples during training to monitor qualitative progress:</p>
<pre><code>Step 0:     "The and the the and of and and and the and and and and..."
Step 2000:  "The king and the lords of the court, and the..."
Step 8000:  "What means this, my lord? I pray thee, speak."
Step 16000: "HAMLET: To be or not to be, that is the question,
            Whether 'tis nobler in the mind to suffer..."
</code></pre>
<p>The progression from random tokens → correct grammar → Shakespeare-like phrases → coherent monologue is visible in samples before it's clearly reflected in the loss curve.</p>

<h2>Learning Rate Schedule: Cosine with Warmup</h2>
<p>Training transformers with a constant learning rate works poorly. Feste uses a cosine schedule with linear warmup:</p>
<ul>
  <li><strong>Warmup phase</strong> (first ~2% of steps): linearly ramp from 0 to max_lr. The model's weights start random; a large learning rate at step 1 would destabilize training immediately.</li>
  <li><strong>Cosine decay</strong> (remaining steps): decay from max_lr to min_lr (typically 1/10 of max_lr) following a cosine curve. This slows learning as the model converges, allowing fine-grained adjustments at the end of training.</li>
</ul>
<pre><code class="language-rust">fn get_lr(&amp;self, step: usize) -&gt; f32 {
    if step &lt; self.warmup_steps {
        return self.max_lr * (step as f32 / self.warmup_steps as f32);
    }
    let progress = (step - self.warmup_steps) as f32
                 / (self.total_steps - self.warmup_steps) as f32;
    let cosine = 0.5 * (1.0 + (std::f32::consts::PI * progress).cos());
    self.min_lr + (self.max_lr - self.min_lr) * cosine
}
</code></pre>

<h2>Checkpointing</h2>
<p>Saving model weights periodically is essential for:</p>
<ul>
  <li>Resuming after a crash without restarting training</li>
  <li>Selecting the best checkpoint (lowest validation loss) rather than the final one</li>
  <li>Transfer learning: saving a pre-trained checkpoint and fine-tuning from it</li>
</ul>
<p>A checkpoint saves all model weights plus optimizer state (Adam's m and v moments) and the current training step. Omitting optimizer state means Adam's moment estimates restart from zero, causing training to behave like the warmup phase again.</p>
<pre><code class="language-rust">pub fn save(&amp;self, path: &amp;Path) -&gt; Result&lt;()&gt; {
    let state = CheckpointState {
        model_weights: self.model.state_dict(),
        optimizer_state: self.optimizer.state_dict(),
        step: self.step,
        best_val_loss: self.best_val_loss,
    };
    let bytes = bincode::serialize(&amp;state)?;
    std::fs::write(path, bytes)?;
    Ok(())
}
</code></pre>

<h2>Performance on CPU</h2>
<p>Feste is designed for CPU training. On a modern laptop (Apple M2, 16GB RAM):</p>
<table>
<thead><tr><th>Config</th><th>Parameters</th><th>Tokens/sec</th><th>Time to 100K steps</th></tr></thead>
<tbody>
<tr><td>Pocket Bard (512d, 6L, 8H)</td><td>~9M</td><td>~8,000</td><td>~3 hours</td></tr>
<tr><td>Medium (512d, 12L, 8H)</td><td>~18M</td><td>~4,000</td><td>~6 hours</td></tr>
</tbody>
</table>
<p>GPU training would be 50–100× faster, but Feste intentionally avoids GPU dependencies to remain accessible on any machine. The tradeoff limits model size to what CPU training can reasonably explore in a weekend.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Perplexity = exp(loss); lower is better; random guessing ≈ vocabulary size.</li>
  <li>Monitor sample quality, not just validation loss — they can diverge during fine-tuning.</li>
  <li>Cosine + warmup: ramp up slowly (avoid early instability), decay gracefully (converge tightly).</li>
  <li>Checkpoints must include optimizer state to resume training cleanly; the best-validation-loss checkpoint is often better than the final one.</li>
</ul>
<p><em>Adapted from Part 4 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Perplexity = exp(cross-entropy loss); trained Feste reaches ~92 vs ~10,000 (random) for a 10K-vocab model.',
    'Sample quality and validation loss can diverge: always generate samples to assess qualitative progress.',
    'Warmup prevents early instability; cosine decay allows tight convergence at the end of training.',
    'Checkpoint = weights + optimizer moments + step; omitting optimizer state resets Adam momentum.',
  ],
  'quiz' => [
    [
      'question' => 'A model trained for 100K steps shows validation loss of 4.52 and training loss of 4.41. What is the validation perplexity, and does this suggest overfitting?',
      'answers' => [
        ['text' => 'Validation perplexity ≈ 91.8 (exp(4.52)). The small gap between train (4.41) and validation (4.52) loss suggests mild overfitting but not severe.', 'correct' => true, 'explanation' => 'exp(4.52) ≈ 91.8. A loss gap of ~0.11 nats is modest for a language model of this size. Severe overfitting would show the validation loss rising while training loss continues falling.'],
        ['text' => 'Validation perplexity ≈ 4.52 (perplexity equals loss). The gap indicates slight underfitting.', 'correct' => false],
        ['text' => 'Validation perplexity ≈ 452 (loss × 100). The training is in the warmup phase.', 'correct' => false],
        ['text' => 'Validation perplexity ≈ 100 (a rounded heuristic). The gap means the model should train longer.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Why does the cosine learning rate schedule include a warmup phase at the start?',
      'answers' => [
        ['text' => 'At initialization, model weights are random and gradients can be very large. A high learning rate immediately would make destructive updates before the optimizer\'s moment estimates stabilize.', 'correct' => true, 'explanation' => 'Adam\'s moment estimates (m and v) start at zero and need a few hundred steps to reflect the true gradient statistics. During this period, the effective learning rate from bias correction can be unstable. A small initial lr protects against large early steps.'],
        ['text' => 'The warmup is needed to pre-train the Adam optimizer state before the model training begins.', 'correct' => false],
        ['text' => 'Warmup prevents the learning rate from exceeding the gradient norm during the first epoch.', 'correct' => false],
        ['text' => 'Warmup is required for cosine decay to produce a valid probability distribution over steps.', 'correct' => false],
      ],
    ],
    [
      'question' => 'You save a checkpoint at step 50,000. If you resume from it, what data must the checkpoint contain for training to continue as if it never stopped?',
      'answers' => [
        ['text' => 'Model weights, optimizer state (Adam\'s m and v moments for every parameter), and the current step number.', 'correct' => true, 'explanation' => 'Model weights determine the forward pass. Optimizer state (moments m and v) determines the next update direction; without them, Adam restarts with zero moments — equivalent to the warmup phase. The step number is needed to continue the learning rate schedule at the right point.'],
        ['text' => 'Only the model weights — the optimizer can reinitialize from the gradients at step 50,001.', 'correct' => false],
        ['text' => 'Model weights and the last batch of training data to recompute gradients.', 'correct' => false],
        ['text' => 'Model weights and the random seed used for dropout and data shuffling.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 13 ──────────────────────────────────────────────────────────────
13 => [
  'title'   => 'Architecture Experiments',
  'module'  => 5,
  'objectives' => [
    'Design and interpret systematic experiments to evaluate architecture choices.',
    'Compare wide-shallow vs narrow-deep transformer configurations.',
    'Explain how vocabulary size and context length affect model quality.',
  ],
  'content_html' => <<<'HTML'
<h2>The Starting Point</h2>
<p>After building a working training pipeline, the Feste experiments begin with a systematic exploration of architectural hyperparameters. The baseline: 6 layers, 8 heads, embed_dim 512, context 256, vocabulary 8,000 — approximately 9M parameters. All experiments train for 100K steps on the full Shakespeare corpus with identical optimization settings.</p>
<p>The evaluation criteria: validation loss (quantitative) and sample quality at step 50K (qualitative). Both matter because, as we saw in Module 4, they can diverge.</p>

<h2>How Many Words Does a Model Need?</h2>
<p>Vocabulary size affects both the embedding table size and the model's ability to represent individual words. Experiments with 4K, 8K, 16K, and 32K token vocabularies showed:</p>
<ul>
  <li>4K: Too small. Many common Shakespeare words split into 2–3 tokens, hurting coherence.</li>
  <li>8K: Good baseline. Most common words are single tokens, reasonable compression ratio.</li>
  <li>16K: Marginal improvement. The embedding table doubles but the sequences aren't much shorter.</li>
  <li>32K: Diminishing returns. The model is now spending most parameters on the embedding tables.</li>
</ul>
<p>The Chinchilla scaling law (Hoffmann et al., 2022) suggests models are often trained on too little data relative to their size. For Feste's 1MB corpus, 8K–10K vocabulary is near-optimal: big enough for coverage, small enough that most parameters go to the transformer layers rather than the embedding table.</p>

<h2>Wide and Shallow vs. Narrow and Deep</h2>
<p>Given a fixed parameter budget (~9M), should we go wide (larger embed_dim, fewer layers) or deep (smaller embed_dim, more layers)? Key results:</p>
<table>
<thead><tr><th>Config</th><th>embed_dim</th><th>n_layers</th><th>Val loss</th><th>Notes</th></tr></thead>
<tbody>
<tr><td>Wide</td><td>1024</td><td>2</td><td>4.71</td><td>High capacity per layer, shallow reasoning</td></tr>
<tr><td>Balanced</td><td>512</td><td>6</td><td>4.52</td><td>Baseline — best overall</td></tr>
<tr><td>Deep</td><td>256</td><td>12</td><td>4.61</td><td>More layers, smaller per-layer capacity</td></tr>
<tr><td>Very deep</td><td>128</td><td>24</td><td>4.89</td><td>Too narrow: each layer can't represent enough</td></tr>
</tbody>
</table>
<p><strong>Conclusion: depth wins within reason, but there's a minimum embed_dim below which performance degrades sharply.</strong> The 512d × 6L balanced configuration outperforms both extremes.</p>

<h2>Cyclops vs. Spider: Attention Head Count</h2>
<p>"Cyclops" = one large head with full embed_dim. "Spider" = many small heads (8–12 heads of 64d each). The spider wins consistently, confirming that multi-head attention learns qualitatively different patterns per head that a single head cannot replicate.</p>
<p>Optimal head count: 8 heads for 512d (64d per head). Going to 16 heads (32d per head) hurts performance — the head dimension becomes too small to represent meaningful query/key relationships. The rule of thumb: head_dim ≥ 64.</p>

<h2>Working Memory: Context Length</h2>
<p>Context length (the maximum sequence length the model attends over) directly affects what the model can "remember" during a forward pass. Experiments with 64, 128, 256, and 512 context lengths:</p>
<ul>
  <li>64 tokens: The model can't track multi-sentence structure. Individual lines can be coherent but scenes fall apart.</li>
  <li>256 tokens: Sweet spot for Shakespeare — covers a complete speech or short exchange.</li>
  <li>512 tokens: Modest improvement. Training slower (quadratic attention), memory doubles.</li>
</ul>
<p>Longer context also stabilizes training: the model sees more coherent text windows, giving better gradient signal for learning grammatical and structural patterns.</p>

<h2>Data Starvation Bug</h2>
<p>An important debugging lesson: early experiments showed suspiciously high variance between training runs with identical hyperparameters. The culprit: the data loader was shuffling at the token level (within-sequence shuffling) rather than at the sequence level. This created "impossible" training examples where the beginning of one speech was paired with the end of another.</p>
<p>Fixing the shuffle to operate on whole sequences dropped validation loss by 0.15 nats and halved training variance. <strong>Data pipeline correctness is as important as architecture.</strong></p>

<h2>Key Takeaways</h2>
<ul>
  <li>Depth beats width for transformers within a parameter budget, but there's a minimum embedding dimension (~256) for expressiveness.</li>
  <li>Multi-head attention (head_dim ≥ 64) consistently outperforms a single large head.</li>
  <li>Context length is a significant quality lever: 256 is a practical minimum for coherent long-form generation.</li>
  <li>Data correctness matters as much as architecture: shuffling whole sequences rather than tokens is essential.</li>
</ul>
<p><em>Adapted from Part 5 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Depth vs width: balanced configs (512d × 6L) beat very wide or very deep at equal parameter counts.',
    'Head_dim ≥ 64 is a practical rule of thumb; too-small heads can\'t represent meaningful Q/K relationships.',
    'Context length 256 is a sweet spot for Shakespeare; longer context improves coherence at quadratic attention cost.',
    'Data pipeline bugs (wrong shuffle granularity) can matter as much as architecture choices.',
  ],
  'quiz' => [
    [
      'question' => 'The experiments show that a 512d × 6L model outperforms a 1024d × 2L model with the same parameters. What explains this?',
      'answers' => [
        ['text' => 'Deeper models can compose representations through multiple attention layers; a 2-layer model has insufficient depth to learn hierarchical linguistic patterns.', 'correct' => true, 'explanation' => 'Each transformer layer can attend to and transform representations from the previous layer. Two layers can learn only 2 levels of abstraction; six layers can build richer hierarchical features. Language has many levels (characters, words, phrases, sentences, scenes).'],
        ['text' => 'Wider models require more training data than the Shakespeare corpus provides; depth is more data-efficient.', 'correct' => false],
        ['text' => 'The 1024d model overfits because its embedding table is too large for the vocabulary size.', 'correct' => false],
        ['text' => 'The matrix multiplication cache blocking is less effective for larger embed_dim matrices.', 'correct' => false],
      ],
    ],
    [
      'question' => 'An experiment uses 16 attention heads with embed_dim=512 (head_dim=32). Performance is worse than 8 heads (head_dim=64). Why?',
      'answers' => [
        ['text' => 'At 32 dimensions, query and key vectors can represent fewer independent directions; dot products become less discriminative, reducing the quality of attention patterns.', 'correct' => true, 'explanation' => 'With only 32 dimensions, there are at most 32 orthogonal directions in the query/key space. Heads with small dimension tend to collapse to similar patterns and lose the "independent views" benefit of multi-head attention.'],
        ['text' => 'More heads mean more parameters in the attention projection matrices, causing overfitting.', 'correct' => false],
        ['text' => 'Sixteen heads are too many for Rayon to parallelize efficiently on 8 CPU cores.', 'correct' => false],
        ['text' => 'The causal mask becomes harder to apply with many heads and blocks gradients.', 'correct' => false],
      ],
    ],
    [
      'question' => 'The data starvation bug shuffled training examples at the token level. What problem did this cause?',
      'answers' => [
        ['text' => 'Token-level shuffling created artificial sequences mixing text from different parts of the corpus, producing "impossible" examples where the model tried to learn transitions that never occur in natural language.', 'correct' => true, 'explanation' => 'If you shuffle individual tokens, a training window might contain the last word of one scene concatenated with the beginning of another. The model wastes capacity learning these false transitions, which raises validation loss and increases variance.'],
        ['text' => 'Token-level shuffling made the training data unreadable, crashing the tokenizer during preprocessing.', 'correct' => false],
        ['text' => 'Token-level shuffling reduced the effective context length because nearby tokens were no longer adjacent.', 'correct' => false],
        ['text' => 'The model memorized the shuffled token order instead of learning language statistics.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 14 ──────────────────────────────────────────────────────────────
14 => [
  'title'   => 'Transfer Learning and the Pocket Bard',
  'module'  => 5,
  'objectives' => [
    'Explain what transfers when fine-tuning a pre-trained model on a new domain.',
    'Describe the TinyStories → Shakespeare transfer learning pipeline.',
    'Interpret perplexity improvements from transfer learning.',
  ],
  'content_html' => <<<'HTML'
<h2>The Pocket Bard</h2>
<p>After architecture experimentation, the best from-scratch configuration — 512d, 6 layers, 8 heads, context 256, vocab 10K — achieves validation perplexity of ~92 after 100K training steps on Shakespeare. This model is named "Pocket Bard."</p>
<p>Perplexity 92 means the model is roughly as uncertain as uniformly guessing among 92 tokens at each position. Shakespeare's vocabulary has ~10,000 tokens; a random model would have perplexity ~10,000. The Pocket Bard generates recognizable Shakespearean text — correct meter, archaic vocabulary, dramatic structure — but with limited semantic coherence. It sounds right but often means nothing.</p>
<pre><code>Sample from Pocket Bard (step 100K):
"HAMLET: What means this, good my lord? What wouldst thou have
Of such a kind as this, that thou art bound to be
The measure of thy soul, that thou art not so?
HORATIO: I do beseech you, sir, to give me leave."
</code></pre>
<p>Correct dramatic form, plausible dialogue — but "the measure of thy soul, that thou art not so" is grammatically and metrically Shakespearean while making no semantic sense.</p>

<h2>Teaching English Before Teaching Shakespeare</h2>
<p>The key insight: Shakespeare is a small dataset (~1MB). A 9M-parameter model has roughly 9 parameters per training token, which is insufficient for generalizing well. The Chinchilla scaling law suggests you need ~20 tokens per parameter for efficient training; Feste has fewer than 1.</p>
<p>Transfer learning hypothesis: pre-train on a larger dataset of simple English text, then fine-tune on Shakespeare. The TinyStories dataset (Eldan &amp; Li, 2023) contains ~2GB of short children's stories generated by GPT-4, written in simple English. Pre-training on TinyStories teaches:</p>
<ul>
  <li>English grammar and syntax</li>
  <li>Common word relationships and collocations</li>
  <li>Narrative structure (beginning, middle, end)</li>
  <li>Dialogue patterns</li>
</ul>
<p>Fine-tuning on Shakespeare then specializes this foundation to archaic vocabulary, dramatic form, and verse structure.</p>

<h2>Transfer Learning Results</h2>
<p>The pipeline:</p>
<ol>
  <li>Retrain the tokenizer on TinyStories (new 10K vocabulary, optimized for simple English)</li>
  <li>Pre-train for 500K steps on TinyStories (starting perplexity drops to ~24.66 on Shakespeare-domain text)</li>
  <li>Fine-tune for 50K steps on Shakespeare</li>
</ol>
<p>Results compared to from-scratch training:</p>
<table>
<thead><tr><th>Method</th><th>Val Perplexity (Shakespeare)</th><th>Training Time</th></tr></thead>
<tbody>
<tr><td>From scratch, 100K steps</td><td>~92</td><td>3 hours</td></tr>
<tr><td>TinyStories pre-train only</td><td>~52 (on Shakespeare eval)</td><td>18 hours</td></tr>
<tr><td>TinyStories → Shakespeare fine-tune</td><td>~47</td><td>18 hours + 1.5 hours</td></tr>
</tbody>
</table>
<p>Fine-tuning reduces perplexity from 52 to 47 — a ~10% improvement from the Shakespeare fine-tuning phase. But the more striking result: the pre-trained model's starting perplexity <em>on Shakespeare</em> is already 24.66 before seeing any Shakespeare. The model learned enough English that it needs far less Shakespeare-specific training.</p>

<h2>What Transfers?</h2>
<p>Transfer learning works because language has universal structure. The embedding and lower-layer attention weights encode:</p>
<ul>
  <li>Word-level semantics (similar words have similar embeddings)</li>
  <li>Basic grammatical relationships (subject-verb agreement, possessives)</li>
  <li>Punctuation and sentence boundary patterns</li>
</ul>
<p>These features are useful for any English text, including archaic Shakespeare. The fine-tuning adjusts upper layers and vocabulary-specific patterns while preserving the general English foundation.</p>

<h2>Fine-tuning Pitfall: Watching the Samples</h2>
<p>The fine-tuning experiments revealed an important phenomenon: validation loss and sample quality diverge. After ~30K fine-tuning steps, validation loss continues to decrease (the model fits Shakespeare better), but sample quality peaks and then degrades — the outputs become increasingly archaic in vocabulary while losing coherent structure.</p>
<p>This "catastrophic forgetting" of general English structure happens when the fine-tuning signal overwhelms the general knowledge in the lower layers. The fix: use a much lower learning rate for fine-tuning (typically 1/10th of pre-training lr) and stop based on sample quality, not validation loss.</p>

<h2>Key Takeaways</h2>
<ul>
  <li>Pre-training on TinyStories reduces Shakespeare perplexity from ~10K (random) to ~52 before any Shakespeare training — general English structure transfers.</li>
  <li>The Chinchilla insight: Feste's Shakespeare corpus is too small for efficient from-scratch training; pre-training compensates.</li>
  <li>Fine-tuning requires a lower learning rate than pre-training; higher rates cause catastrophic forgetting of transferred knowledge.</li>
  <li>Monitor sample quality, not just validation loss — fine-tuning can overfit the domain while appearing to improve numerically.</li>
</ul>
<p><em>Adapted from Part 5 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Pre-train on TinyStories: starting perplexity on Shakespeare is ~52 before any Shakespeare exposure.',
    'Transfer works because lower layers learn universal English structure (grammar, word relations).',
    'Fine-tuning at high lr causes catastrophic forgetting; use 1/10th of pre-training lr.',
    'Sample quality peaks before validation loss does during fine-tuning — use both metrics.',
  ],
  'quiz' => [
    [
      'question' => 'After TinyStories pre-training, the model\'s perplexity on Shakespeare text is 52 before seeing any Shakespeare. What explains this?',
      'answers' => [
        ['text' => 'English grammar, vocabulary structure, and narrative patterns are shared between TinyStories and Shakespeare; the model learned these universal features during pre-training.', 'correct' => true, 'explanation' => 'Shakespeare uses archaic words and dramatic form, but the underlying grammar (subject-verb-object, punctuation, sentence structure) is fundamentally the same as modern English. Pre-training captures this shared structure.'],
        ['text' => 'TinyStories contains Shakespearean passages since it was generated by GPT-4 which was trained on Shakespeare.', 'correct' => false],
        ['text' => 'The tokenizer was retrained on Shakespeare for the evaluation, giving the pre-trained model an advantage.', 'correct' => false],
        ['text' => 'Perplexity 52 represents a random baseline for the 10K vocabulary; the model has not actually learned anything.', 'correct' => false],
      ],
    ],
    [
      'question' => 'During Shakespeare fine-tuning, validation loss keeps decreasing while sample quality peaks and then degrades. What is happening?',
      'answers' => [
        ['text' => 'The model is overfitting to Shakespeare\'s surface patterns (archaic vocabulary, meter) while catastrophically forgetting the general English structure learned during pre-training.', 'correct' => true, 'explanation' => 'Fine-tuning with a high learning rate updates lower-layer weights aggressively toward Shakespeare-specific patterns, overwriting the general English representations that made the transfer useful. Validation loss decreases (better fit) but coherence (which needs general language understanding) degrades.'],
        ['text' => 'The validation set was accidentally contaminated with training data, causing misleadingly low validation loss.', 'correct' => false],
        ['text' => 'Sample quality is a subjective metric; the model is actually improving continuously.', 'correct' => false],
        ['text' => 'The learning rate schedule has a bug that causes the model to memorize the training set.', 'correct' => false],
      ],
    ],
    [
      'question' => 'The Chinchilla scaling law says models should be trained on ~20 tokens per parameter. Feste\'s 9M-parameter model trains on ~1M tokens. What does this imply?',
      'answers' => [
        ['text' => 'The model is massively over-parameterized for its training data; it will overfit. Transfer learning compensates by bringing in more (pre-training) data effectively.', 'correct' => true, 'explanation' => 'Chinchilla-optimal training requires 20× more tokens than parameters. With 9M parameters and 1M tokens, Feste has ~18× fewer tokens than optimal. The model memorizes rather than generalizing. Pre-training on ~2GB TinyStories (2B tokens) brings it much closer to the optimal compute-data ratio.'],
        ['text' => 'The model should add more layers until it has ~20M parameters to match the token count.', 'correct' => false],
        ['text' => 'The Shakespeare corpus should be upsampled 20× to create a 20M token dataset.', 'correct' => false],
        ['text' => 'The learning rate should be increased by 20× to see each training token 20 times.', 'correct' => false],
      ],
    ],
  ],
],

// ─── Lesson 15 ──────────────────────────────────────────────────────────────
15 => [
  'title'   => 'What We Built and What We Learned',
  'module'  => 5,
  'objectives' => [
    'Assess what statistical language models can and cannot learn.',
    'Identify the key architectural and training decisions that most impacted Feste\'s quality.',
    'Describe the path from Feste to a production-scale LLM.',
  ],
  'content_html' => <<<'HTML'
<h2>What Worked</h2>
<p>Looking back across the 15 lessons of this course, several decisions had outsized impact on Feste's quality:</p>

<h3>1. BPE Tokenization at the Right Vocabulary Size</h3>
<p>The 10K-token vocabulary was close to optimal for Shakespeare. Larger vocabularies wasted parameters on the embedding table; smaller ones hurt token quality. The BPE tokenizer's domain adaptation (common Shakespeare words becoming single tokens) meaningfully improved the model's ability to learn poetic meter.</p>

<h3>2. Cache-Blocked Matrix Multiplication</h3>
<p>The 6.7× single-threaded speedup from cache blocking, combined with Rayon parallelism, made 100K-step training feasible in hours rather than days. Without this, architectural experimentation would have been impractical on CPU.</p>

<h3>3. Balanced Architecture (512d × 6L × 8H)</h3>
<p>The systematic experiments confirmed: balanced configurations outperform extremes. Neither very wide-shallow nor very narrow-deep models matched the baseline at equal parameter counts. The head_dim=64 rule kept attention patterns meaningful.</p>

<h3>4. Transfer Learning from TinyStories</h3>
<p>The single largest quality jump — from perplexity 92 (from scratch) to perplexity 47 (with transfer) — came from pre-training on a 2,000× larger dataset. This confirmed the Chinchilla insight: small models on small datasets benefit more from more data than from more parameters.</p>

<h3>5. Watching Samples, Not Just Loss</h3>
<p>The validation loss can be misleading. The decision to stop fine-tuning based on sample quality (rather than minimum validation loss) prevented catastrophic forgetting and produced a model that sounds like Shakespeare rather than a model that merely fits Shakespeare's token distribution.</p>

<h2>What We Built: The Limits of Statistical Learning</h2>
<p>The Pocket Bard generates text that is:</p>
<ul>
  <li><strong>Syntactically correct:</strong> Grammatical structures are almost always valid.</li>
  <li><strong>Stylistically authentic:</strong> Archaic vocabulary, iambic pentameter, dramatic form.</li>
  <li><strong>Thematically random:</strong> Individual sentences may be profound-sounding but rarely build toward coherent themes.</li>
</ul>
<p>This is the fundamental insight of the project — and perhaps the most important lesson of this course: <strong>statistical learning masters syntax, format, and rhythm. It cannot learn semantics from pattern frequency alone.</strong></p>
<p>A human reading Shakespeare knows that Hamlet's "To be or not to be" is a meditation on existence and suicide — not because those words frequently co-occur with those concepts in the training data, but because of causal reasoning about the character's situation, the play's narrative arc, and the speaker's psychology. No amount of next-token prediction training will give the model access to that kind of understanding.</p>
<p>The Dalí analogy: a statistical model is like Salvador Dalí who can paint in any style with photographic accuracy but has no message beyond the accuracy itself. The form is learned; the content remains the model's own statistical echo.</p>

<h2>The Path Forward</h2>
<p>From Feste's 9M parameters to GPT-4's estimated 1.8 trillion, the architectural ideas are the same — the scale is not. What changes with scale:</p>
<ul>
  <li><strong>Emergent capabilities:</strong> Above ~1B parameters, models exhibit abilities (complex reasoning, code generation, in-context learning) that don't appear in smaller models.</li>
  <li><strong>Training data quality:</strong> At scale, data curation matters more than data quantity.</li>
  <li><strong>RLHF and alignment:</strong> Raw language modeling produces a simulator of text; RLHF (Reinforcement Learning from Human Feedback) steers the model toward helpfulness, harmlessness, and honesty.</li>
  <li><strong>Inference efficiency:</strong> Deploying a trillion-parameter model requires quantization, speculative decoding, and specialized hardware — a whole engineering domain built on the foundations in this course.</li>
</ul>
<p>Feste is not a production LLM. But every component of every production LLM — the tokenizer, the tensor operations, the attention mechanism, the training infrastructure — is a scaled-up version of what you've built here. Understanding the foundations makes you a more effective practitioner at any scale.</p>

<h2>Where to Go From Here</h2>
<ul>
  <li>Explore the <a href="https://github.com/jeremyandrews/feste">Feste GitHub repository</a> and experiment with hyperparameters.</li>
  <li>Read the original Transformer paper: "Attention Is All You Need" (Vaswani et al., 2017)</li>
  <li>Read the GPT-2 paper: "Language Models are Unsupervised Multitask Learners" (Radford et al., 2019)</li>
  <li>Explore Andrej Karpathy's nanoGPT — a minimal Python implementation of the same ideas</li>
  <li>Try implementing FlashAttention to see how memory-efficient attention changes the scaling story</li>
</ul>

<h2>Key Takeaways</h2>
<ul>
  <li>Statistical language models master syntax and form; semantics and understanding require something beyond next-token prediction.</li>
  <li>Transfer learning is the highest-leverage improvement for small-data fine-tuning tasks.</li>
  <li>The Feste architecture — tokenizer, tensors, attention, MLP, training — is a microcosm of every large language model in production today.</li>
  <li>Understanding foundations at this level of detail makes you a more effective practitioner at every scale.</li>
</ul>
<p><em>Adapted from Part 5 of "Building an LLM From Scratch in Rust" by Jeremy Andrews, published on tag1.com</em></p>
HTML,
  'key_takeaways' => [
    'Statistical models learn syntax and style; semantics and meaning require understanding beyond pattern frequency.',
    'Transfer learning (TinyStories→Shakespeare) was the single biggest quality improvement in the project.',
    'Every component of a trillion-parameter LLM is a scaled version of what Feste implements.',
    'Watching sample quality matters: validation loss can improve while actual model coherence degrades.',
  ],
  'quiz' => [
    [
      'question' => 'Feste generates syntactically correct, stylistically authentic Shakespeare but lacks semantic coherence. What is the fundamental limitation?',
      'answers' => [
        ['text' => 'Next-token prediction learns statistical correlations between tokens, not causal understanding of meaning, narrative, or speaker intent.', 'correct' => true, 'explanation' => 'The model learns that certain words co-occur in Shakespearean patterns. It does not understand that Hamlet is a character contemplating mortality, or that "To be or not to be" follows from the play\'s narrative. Statistical correlations can reproduce form; they cannot generate meaning.'],
        ['text' => 'The model is too small (9M parameters) to learn semantics; a 100M parameter model would be coherent.', 'correct' => false],
        ['text' => 'Semantic coherence requires a different tokenizer — word-level rather than subword BPE.', 'correct' => false],
        ['text' => 'The Shakespeare corpus is too old for the model to understand, since modern English grammar has changed.', 'correct' => false],
      ],
    ],
    [
      'question' => 'Ranking the improvements by validation perplexity gain, which had the largest single impact on Feste\'s quality?',
      'answers' => [
        ['text' => 'Transfer learning from TinyStories: reduced perplexity from ~92 to ~47, a 48% improvement.', 'correct' => true, 'explanation' => 'The systematic experiments showed architecture tuning improved perplexity by a few points. Transfer learning halved the perplexity. More data (even via pre-training) consistently outweighs architecture tweaks for small models.'],
        ['text' => 'Cache-blocked matrix multiplication: reduced training time enabling more hyperparameter experiments.', 'correct' => false],
        ['text' => 'Increasing context length from 64 to 256 tokens: allowed the model to learn cross-sentence patterns.', 'correct' => false],
        ['text' => 'Fixing the data starvation bug: reduced validation loss by 0.15 nats (a ~15% perplexity improvement).', 'correct' => false],
      ],
    ],
    [
      'question' => 'What is the key architectural difference between Feste and GPT-4, beyond parameter count?',
      'answers' => [
        ['text' => 'The fundamental architecture (tokenizer, attention, MLP, residuals, layer norm) is the same; GPT-4 adds scale, better data curation, and RLHF alignment — not a different architecture.', 'correct' => true, 'explanation' => 'GPT-4 uses the same transformer decoder architecture as Feste. The differences are: ~200,000× more parameters, vastly more training data, extensive data curation, and RLHF to align the model with human preferences. The foundations are identical.'],
        ['text' => 'GPT-4 uses a fundamentally different attention mechanism (linear attention) that avoids the O(T²) bottleneck.', 'correct' => false],
        ['text' => 'GPT-4 uses recurrent layers in the lower levels and attention only in the upper layers.', 'correct' => false],
        ['text' => 'GPT-4 replaces BPE tokenization with a continuous embedding of raw audio and text.', 'correct' => false],
      ],
    ],
  ],
],

  ]; // end return
} // end function
