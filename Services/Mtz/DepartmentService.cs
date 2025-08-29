using Jas.Data.JasMtzDb;
using Microsoft.EntityFrameworkCore;

namespace Jas.Services.Mtz
{
    public interface IDepartmentService
    {
        Task InitializeAsync();
        JasDepartment? GetDepartment(int? DepartmentId);
    }

    public class DepartmentService : IDepartmentService
    {
        private readonly JasMtzDbContext _dbContext;
        private Dictionary<int, JasDepartment> _Departments;

        public DepartmentService(JasMtzDbContext dbContext)
        {
            _dbContext = dbContext;
        }

        public async Task InitializeAsync()
        {
            if (_Departments is null)
            {
                var Departments = await _dbContext.JasDepartments.ToListAsync();
                _Departments = Departments.ToDictionary(u => u.Id, u => u);
            }
        }

        public JasDepartment? GetDepartment(int? DepartmentId)
        {

            if (DepartmentId is null || _Departments is null) return null;

            return _Departments.TryGetValue((int)DepartmentId, out var Department) ? Department : null;
        }
    }

}
