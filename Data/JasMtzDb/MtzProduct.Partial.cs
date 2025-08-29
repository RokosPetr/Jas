namespace Jas.Data.JasMtzDb
{
    public partial class MtzProduct
    {
        public virtual ICollection<MtzProductAttribute> MtzProductAttributes { get; set; } = new List<MtzProductAttribute>();
    }
}
