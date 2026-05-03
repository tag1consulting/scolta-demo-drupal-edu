# Content Sources and Provenance

This document describes the sources and provenance for content on the Meridian AI demo site. All content was written originally for this demonstration, but draws on real technical literature, real AI systems, and real-world practices.

---

## Content Philosophy

Meridian AI content was written to be **technically accurate and educationally useful**, not merely plausible-sounding filler. Where possible, specific model names, algorithm names, paper titles, and benchmark names are real. Where the content refers to people or institutions, those are either fictional (Meridian faculty) or real and accurately described (e.g., references to Yoshua Bengio's work, the EU AI Act).

The primary audiences for Scolta demonstrations are Drupal practitioners evaluating the search platform. That audience benefits more from content that teaches something real about AI than from lorem ipsum with AI vocabulary.

---

## Real Technical References Embedded in Content

### Transformer Architecture
- "Attention Is All You Need" (Vaswani et al., 2017) — foundational transformer paper, referenced in multiple lecture pages and articles
- GPT-2 architecture — referenced in lecture pages; the specific GPT-2 configuration (768 hidden dims, 12 layers, 12 heads) is accurate
- RoPE (Rotary Position Embedding) — real technique from Su et al., 2021; described accurately in positional encoding article
- ALiBi (Attention with Linear Biases) — real technique from Press et al., 2021; described accurately

### Training and Fine-tuning
- RLHF (Reinforcement Learning from Human Feedback) — real technique, described accurately with reference to InstructGPT paper
- DPO (Direct Preference Optimization) — real technique from Rafailov et al., 2023; described accurately
- LoRA (Low-Rank Adaptation) — real PEFT technique from Hu et al., 2021; parameters (rank r, alpha) described accurately
- QLoRA — real technique from Dettmers et al., 2023; described accurately
- Flash Attention — real technique from Dao et al., 2022; described accurately

### Optimization
- Adam optimizer — real; β₁=0.9, β₂=0.999, ε=1e-8 defaults are accurate
- AdamW — real; weight decay separation is accurate
- Gradient clipping to norm 1.0 — common practice, described accurately

### Information Retrieval
- BM25 — real probabilistic IR model; formula (TF×IDF with saturation) described accurately
- TF-IDF — real; described accurately
- NDCG@10 — real evaluation metric; described accurately
- Pagefind — real static search library; version 1.5.2 used in the demo
- Scolta — real open-source search platform by Tag1 Consulting; described accurately throughout

### Computer Vision
- ViT (Vision Transformer) — real; patch-based architecture described accurately
- CLIP (Contrastive Language-Image Pretraining) — real OpenAI model; described accurately
- SAM (Segment Anything Model) — real Meta model; described accurately
- DDPM (Denoising Diffusion Probabilistic Models) — real; forward/reverse process described accurately
- DDIM — real faster sampling technique; described accurately
- NeRF (Neural Radiance Fields) — real; volume rendering concept described accurately

### Reinforcement Learning
- Q-learning — real algorithm; Bellman equation described accurately
- PPO (Proximal Policy Optimization) — real algorithm; clip ratio described accurately
- SAC (Soft Actor-Critic) — real algorithm; entropy term described accurately
- DreamerV3 — real world model; described accurately with reference to published work
- MDP (Markov Decision Process) — real formalism; (S, A, T, R, γ) described accurately

### Benchmarks and Evaluation
- MMLU — real benchmark
- HumanEval — real code generation benchmark
- HELM — real holistic evaluation framework
- ARC-Challenge — real benchmark
- AlignBench — fictional Meridian project, but modeled on real alignment evaluation work

### Regulatory and Governance
- EU AI Act — real regulation; risk tier descriptions (Unacceptable, High Risk, Limited Risk, Minimal Risk) are accurate
- NIST AI RMF — real framework; described accurately

---

## Tag1 Consulting and Scolta References

The following references to Tag1 and Scolta in content are accurate:

**Feste blog series** — The "GPT-2 in Rust" implementation documented on the Tag1 blog is real and used as curriculum material in LLM Engineering courses. Articles referencing it link to the real Tag1 blog.

**Scolta platform** — Scolta is a real open-source Drupal module developed by Tag1. The technical description of its architecture (Pagefind + query expansion + LLM overviews via Anthropic Claude API) is accurate. The article "Scolta: How Pagefind, Query Expansion, and AI Overviews Work Together" in the SAI school resources describes the system accurately.

**Tag1 AI governance blog** — Referenced in SSG content as a real resource on responsible AI governance for Drupal organizations.

---

## Fictional Elements

### Meridian AI as Institution
- All named faculty are fictional
- The geography (coastal redwoods + desert mesa + freshwater sea + lake-effect snow) is physically impossible. This is intentional — see the Simpsons note below.
- "Estero Bay" as nearby city is fictional
- All research project names and results are fictional, though modeled on real research programs
- The AlignBench, ML-IR Bench 2026, and Canary drift detection system are fictional projects modeled on real work in their respective areas

### The Impossible Geography
The impossible location of Meridian AI is a deliberate signal, in the tradition of the Simpsons' Springfield, that this is a fictional place. No combination of coastal redwoods, high desert mesa, freshwater inland sea with tidal behavior, and lake-effect snow is geophysically possible on Earth. Sophisticated visitors should recognize this immediately. The geography is described lovingly in the Campus Life page because it is genuinely delightful to read.

### Paper Titles and Conference Results
- All specific paper titles attributed to Meridian faculty are fictional, though many are modeled on real research areas
- ICML acceptance and award results are fictional
- NSF CAREER Award to Dr. Shankar is fictional
- All fellowship award descriptions are fictional

---

## Content Accuracy Notes

### PyTorch Code Examples
The code in lecture pages is functional and represents standard PyTorch patterns as of 2025-2026. The GPT implementation in "The Transformer Architecture Step by Step" is a simplified but working implementation following Andrej Karpathy's nanoGPT style.

### API Examples
The Claude API examples in "Prompt Engineering as Software Engineering" use the `anthropic` Python SDK with the `claude-3-5-sonnet` (updated to `claude-sonnet-4-6` in the demo) model. Syntax is accurate for the SDK version current at time of writing.

### BPE Tokenizer
The BPE tokenizer implementation in "Building a Tokenizer From Scratch" is a simplified but correct implementation of the Byte-Pair Encoding algorithm as used in GPT-2.

---

## What This Document Is For

This document is provided for:

1. **Demo presenters** who need to accurately describe what is real vs. fictional when showing the site to prospects
2. **Scolta evaluators** who want to understand the provenance of the content they are searching
3. **Technical reviewers** who want to verify content accuracy before using the site in a sales or education context
