import styles from './SkeletonCard.module.css';

export default function SkeletonCard() {
  return (
    <article className={styles.card} aria-hidden="true">
      <div className={`skeleton ${styles.image}`} />
      <div className={styles.body}>
        <div className={`skeleton ${styles.tag}`} />
        <div className={`skeleton ${styles.title}`} />
        <div className={`skeleton ${styles.titleShort}`} />
        <div className={styles.footer}>
          <div className={`skeleton ${styles.price}`} />
          <div className={`skeleton ${styles.btn}`} />
        </div>
      </div>
    </article>
  );
}
