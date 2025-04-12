Your task is to **move all business logic for finding entities** from **Activities** and **Controllers** into
repositories while ensuring that repositories remain **readonly** (i.e., no creation, updates, or deletions inside
repositories).

---

### **Key Guidelines:**

1. **Encapsulation of Query Logic:**
    - Analyze the provided code and identify where entity retrieval logic is scattered across **Activities** and
      **Controllers**.
    - Extract this logic into **repositories**, ensuring they only handle **query operations**.
    - Do not modify or introduce logic related to creating, updating, or deleting entities within repositories.

2. **Interface-Driven Design:**
    - Identify the required methods for repositories based on actual usage in the application.
    - Define **interfaces** for repositories and move implementations into their respective repository classes.
    - Do not add unnecessary methods—only implement those actually used in the application.

3. **Consistent Method Naming Convention:**
    - Follow a **clear distinction** between methods that return nullable results and those that must throw exceptions:
        - **`findXXX(...)`** → Used when an entity **may or may not be found**, returning `null` if not found.
        - **`getXXX(...)`** → Used when an entity **must exist**, throwing an exception if not found.
    - If you find code using `findXXX(...)` followed by an explicit exception throw, **replace it with a `getXXX(...)`
      method** inside the repository.

4. **Code Optimization & Clean-Up:**
    - Remove redundant logic from **Controllers** and **Activities** once moved to repositories.
    - Ensure **repository methods are reusable** to avoid code duplication.
    - Keep **service layers thin**, delegating data-fetching responsibility fully to repositories.

By following these principles, repositories will become the **single source of truth** for querying entities,
improving maintainability and consistency in the application.