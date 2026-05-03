# Showcase Queries for Meridian AI Demo

Use these queries to demonstrate Scolta's AI-powered search capabilities to EDU prospects. Queries are organized by complexity, content type, and the specific Scolta capability they showcase.

For each query, expected behavior is described. "AI overview" means Scolta generates an introductory synthesis before the results list.

---

## Tier 1: Basic Semantic Search

These queries demonstrate that Scolta finds the right content even without keyword match.

**"how do attention mechanisms work"**
Expected: LLM-401 course page, transformer lecture, attention mechanism article. AI overview explains self-attention in intuitive terms. Notably does NOT require the word "attention" to appear in result titles.

**"I want to learn about building AI agents"**
Expected: Certificate in AI Agents program page, AGT-series courses, relevant articles on tool use and MCP. Matches on "AI agents" concept even though some pages say "autonomous agents."

**"what is gradient descent"**
Expected: MATH-430 course, optimization article, relevant SFM lectures. AI overview explains the algorithm before results.

**"careers in AI ethics"**
Expected: MS in AI Ethics, SSG faculty profiles, ETH-series courses, AI & labor article, career/why-AI page. Demonstrates cross-content-type synthesis.

**"reinforcement learning for robotics"**
Expected: Certificate in AI for Robotics, SDC school content, RL courses, Threshold Robotics Lab research project. Shows topic-crossing results.

---

## Tier 2: Question-Style Queries

These queries demonstrate the AI overview capability most clearly.

**"what is the difference between RLHF and DPO"**
Expected: Strong AI overview explaining both techniques, the tradeoffs, and when each is preferred. Results include the RLHF vs. DPO article, LLM alignment courses, faculty like Dr. Kwame Asante.

**"why do transformers use positional encoding"**
Expected: AI overview explaining the motivation (permutation invariance), then results covering RoPE, ALiBi, and the transformer architecture lecture. Demonstrates understanding of a "why" question.

**"how does Scolta search work"**
Expected: Prominent result for the Scolta case study article ("Scolta: How Pagefind, Query Expansion, and AI Overviews Work Together"), IR-101 course, the demo disclaimer page. AI overview should accurately describe the Pagefind + query expansion + LLM overview stack.

**"what is the EU AI Act and how does it affect ML practitioners"**
Expected: EU AI Act article, AI governance content, SSG school. AI overview synthesizes the risk tiers and practical implications.

**"explain backpropagation intuitively"**
Expected: SFM foundational courses, optimization article, relevant lecture pages. Demonstrates handling of an explanation request.

**"when should I use a vector database"**
Expected: Vector database article from SAI, RAG systems article, search and IR courses. AI overview frames the "when" question around use cases.

---

## Tier 3: Cross-Content Discovery

These queries demonstrate Scolta's ability to surface connections across content types.

**"who at Meridian works on multilingual AI"**
Expected: Dr. Elena Marchetti (search + multilingual), Project Lighthouse research project, ML-IR Bench article, Isabel Ferreira fellowship news. Cross-references faculty → research → news.

**"courses I should take before the LLM Engineering program"**
Expected: MS in LLM Engineering program page with prerequisites, foundational math courses (MATH-401, MATH-430), LLM-401 (first course), introduction articles. Shows prerequisite reasoning.

**"what research is Meridian doing on AI safety"**
Expected: AlignBench project, SAF-201 course, Dr. James Okafor and Dr. Kwame Asante profiles, AI safety articles, Canary system news item. Complete cross-type picture.

**"how do I fine-tune an open-source LLM"**
Expected: Fine-tune vs. prompt article, LoRA/QLoRA article, LLM-450 course (fine-tuning), MLOps courses, relevant faculty. Practical question with multiple relevant result types.

**"faculty who work on both math and machine learning"**
Expected: Dr. Ingrid Holmberg (SFM chair), Dr. Ravi Shankar (optimization), possibly Dr. Elias Müller (RL theory). Cross-faculty-taxonomy discovery.

---

## Tier 4: Specificity and Precision

These queries demonstrate accurate retrieval of specific technical content.

**"BPE tokenization algorithm"**
Expected: Tokenizer lecture page ("Building a Tokenizer From Scratch"), tokenization article, LLM-402 course. High-precision match on a specific technical term.

**"DreamerV3 world model"**
Expected: World models article, SDC research content, Dr. Elias Müller profile. Specific model name lookup.

**"NSF CAREER award"**
Expected: Dr. Ravi Shankar news item. Precise document retrieval.

**"Pagefind static site search"**
Expected: Scolta case study article, IR-101 course ("How Search Engines Use AI" lecture), demo page. Specific technology term.

**"sim-to-real transfer in robotics"**
Expected: Sim-to-real article (SDC), Threshold Robotics Lab, Certificate in AI for Robotics. Technical term retrieval.

---

## Tier 5: Navigational Queries

These demonstrate Scolta's usefulness for navigating a large content site.

**"Meridian AI admissions requirements"**
Expected: Admissions page. Navigational query; should surface the page directly.

**"spring symposium"**
Expected: Spring symposium news item. Recent event lookup.

**"about this demo"**
Expected: `/about/demo` page as top result. Direct navigation.

**"Tag1 Consulting"**
Expected: Industry Partners page, Scolta case study article, demo page. All references to Tag1.

**"Dr. Priya Chakraborty"**
Expected: Faculty profile, SDC research projects, robotics courses. Faculty member lookup.

---

## Tier 6: Ambiguous and Exploratory

These demonstrate graceful handling of vague or broad queries.

**"AI safety"**
Expected: AI overview synthesizing safety as a field, then diverse results: SAF-201, AlignBench, SSG school, Canary news, safety articles. Good breadth.

**"learn machine learning from scratch"**
Expected: AI overview suggesting a learning path (math foundations → core ML → specialization), results spanning SFM foundational courses, intro articles, AI Literacy Certificate. Demonstrates pedagogical synthesis.

**"what makes Meridian different from other AI programs"**
Expected: About page, Why Study AI page, possibly Campus Life. Should surface institutional differentiation content.

**"AI and healthcare"**
Expected: Clinical AI research project, Dr. Fatima Al-Rashid profile, healthcare AI article, Archipelago partnership news, ETH content on healthcare AI. Cross-type discovery on a theme.

**"open source LLMs"**
Expected: Open-source LLM landscape article, Open LLM Reproducibility Initiative research project, relevant courses. Topic-based clustering.

---

## Tier 7: Edge Cases and Stress Tests

These are good for demonstrating robustness.

**"transformer" (single word)**
Expected: Many relevant results but AI overview should help orient. Pagefind handles single-word queries; Scolta adds the overview context.

**"how do I get a job in AI"**
Expected: Why Study AI page, Careers content, SAI school programs, placement/admissions content. Pragmatic question with institutional content.

**"Claude API"**
Expected: Prompt engineering lecture (uses Claude API examples), Scolta case study, possibly AI overview article. Specific technology reference.

**"what is Meridian Sea"**
Expected: About page, Campus Life. Geography lookup that appears in narrative content, not structured fields.

**"MoE mixture of experts"**
Expected: MoE article (SLR batch 2), possibly LLM-401. Tests retrieval of an abbreviation with spelled-out form in the document.

**"lora fine-tuning" (lowercase, no hyphen)**
Expected: LoRA article, fine-tuning courses. Tests case/punctuation normalization.

---

## Presenter Notes

### Setting Up for a Demo

1. Ensure `ddev start` has completed and `drush scolta:build` has run
2. Set a real Anthropic API key in the Scolta settings
3. Open the site at `https://meridian-ai.ddev.site`
4. The search bar is in the `content_above` region, prominently placed

### What to Emphasize

**For technical audiences**: Lead with Tier 5-7 queries that show cross-type discovery. Then open an AI overview and explain the Pagefind + query expansion + Claude pipeline. The Scolta case study article on the site describes the architecture accurately.

**For content/editorial audiences**: Lead with Tier 2 question-style queries showing the AI overview. Demonstrate that the overview synthesizes across multiple pages rather than copying a single result.

**For decision-makers**: Lead with Tier 1 semantic queries. Show the contrast with a keyword search failure (try "attention mechanism" on a site with keyword search only), then show Scolta finding it through the question form.

### Known Showcase Moments

The most reliably impressive demo sequence:
1. Ask "what is the difference between RLHF and DPO" — gets a strong AI overview with a real technical comparison
2. Click through to the RLHF vs DPO article — show that the overview was grounded in real content
3. Ask "who at Meridian teaches this topic" — show cross-type discovery to faculty profiles
4. Ask "what courses cover this" — show the course connections

This sequence demonstrates semantic search, AI synthesis, and cross-content navigation in about 3 minutes.
